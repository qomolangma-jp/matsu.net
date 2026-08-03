<?php

namespace App\Services;

use App\Models\User;
use App\Models\News;
use App\Models\Event;
use App\Models\LineNotificationLog;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class LineMessagingService
{
    private ?string $channelAccessToken;
    private string $apiUrl = 'https://api.line.me/v2/bot/message/push';
    private bool $lineLimitExceeded = false;
    private int $remainingCount = 0;

    public function __construct()
    {
        // DB設定が空でなければ優先、なければenv設定を使用
        $dbToken = Setting::get('line_channel_access_token', '');
        $this->channelAccessToken = ($dbToken !== '') 
            ? $dbToken 
            : (config('services.line.messaging_channel_access_token') ?: null);
    }

    /**
     * News/Event を対象にLINE送信し、ログに記録する
     * 月間上限超過時はメール送信にフォールバック
     *
     * @param News|Event $notifiable
     * @param bool $resendAll true=全員へ再送（既送信含む）  false=未送信のみ
     * @return array ['success_count', 'failure_count', 'errors', 'target_count', 'line_count', 'email_count', 'remaining_line_quota']
     */
    public function sendNotification($notifiable, bool $resendAll = false): array
    {
        // 対象ユーザーのベースクエリ（承認済み・LINE ID あり）
        $usersQuery = User::approved()->whereNotNull('line_id');

        if (!empty($notifiable->target_roles)) {
            $usersQuery->whereIn('role', $notifiable->target_roles);
        }

        if ($notifiable instanceof News && !empty($notifiable->target_graduation_years)) {
            $usersQuery->whereIn('graduation_year', $notifiable->target_graduation_years);
        } elseif ($notifiable instanceof Event && $notifiable->graduation_year) {
            $usersQuery->where('graduation_year', $notifiable->graduation_year);
        }

        if (!$resendAll) {
            // 既送信ユーザーを除外
            $sentUserIds = $notifiable->lineNotificationLogs()
                ->pluck('user_id')
                ->unique()
                ->toArray();
            if (!empty($sentUserIds)) {
                $usersQuery->whereNotIn('id', $sentUserIds);
            }
        }

        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            return [
                'success_count' => 0,
                'failure_count' => 0,
                'errors'        => [],
                'target_count'  => 0,
                'line_count'    => 0,
                'email_count'   => 0,
                'remaining_line_quota' => LineNotificationLog::getRemainingCount(),
            ];
        }

        // 月間制限をチェック
        $this->remainingCount = LineNotificationLog::getRemainingCount();
        $this->lineLimitExceeded = $this->remainingCount <= 0;

        $messages = $notifiable instanceof News
            ? $this->buildNewsMessage($notifiable)
            : $this->buildEventMessage($notifiable);

        $successCount = 0;
        $failureCount = 0;
        $lineCount = 0;
        $emailCount = 0;
        $errors = [];
        $currentMonth = Carbon::now()->format('Y-m');

        foreach ($users as $user) {
            try {
                // LINE月間制限をチェック
                if ($this->lineLimitExceeded || $this->remainingCount <= 0) {
                    // メール送信にフォールバック
                    if ($user->email) {
                        $result = $this->sendEmailNotification($user, $notifiable);
                        if ($result) {
                            $successCount++;
                            $emailCount++;
                        } else {
                            $failureCount++;
                            $errors[] = "{$user->full_name}: メール送信失敗";
                        }
                    } else {
                        $failureCount++;
                        $errors[] = "{$user->full_name}: LINE上限超過かつメールアドレス未登録";
                    }
                } else {
                    // LINE送信を試行
                    $result = $this->sendPushMessage($user->line_id, $messages);
                    if ($result) {
                        $successCount++;
                        $lineCount++;
                        $this->remainingCount--;

                        // 送信ログを記録
                        LineNotificationLog::create([
                            'notifiable_type' => get_class($notifiable),
                            'notifiable_id'   => $notifiable->id,
                            'user_id'         => $user->id,
                            'notification_month' => $currentMonth,
                        ]);
                    } else {
                        $failureCount++;
                    }
                }
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "{$user->full_name}: {$e->getMessage()}";
                Log::error('LINE/Email送信エラー', [
                    'user_id'         => $user->id,
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id'   => $notifiable->id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors'        => $errors,
            'target_count'  => $users->count(),
            'line_count'    => $lineCount,
            'email_count'   => $emailCount,
            'remaining_line_quota' => max(0, $this->remainingCount),
        ];
    }

    /**
     * ニュースをLINEで送信（ダミー実装）
     * 
     * @param News $news
     * @param Collection $users
     * @return array
     */
    public function sendNewsNotification(News $news, Collection $users): array
    {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($users as $user) {
            if (!$user->line_id) {
                $failureCount++;
                $errors[] = "{$user->full_name}: LINE IDが未登録";
                continue;
            }

            try {
                $result = $this->sendPushMessage(
                    $user->line_id,
                    $this->buildNewsMessage($news)
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "{$user->full_name}: {$e->getMessage()}";
                Log::error('LINE送信エラー', [
                    'user_id' => $user->id,
                    'news_id' => $news->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
        ];
    }

    /**
     * イベント通知をLINEで送信（ダミー実装）
     * 
     * @param Event $event
     * @param Collection $users
     * @return array
     */
    public function sendEventNotification(Event $event, Collection $users): array
    {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($users as $user) {
            if (!$user->line_id) {
                $failureCount++;
                $errors[] = "{$user->full_name}: LINE IDが未登録";
                continue;
            }

            try {
                $result = $this->sendPushMessage(
                    $user->line_id,
                    $this->buildEventMessage($event)
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "{$user->full_name}: {$e->getMessage()}";
                Log::error('LINE送信エラー', [
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
        ];
    }

    /**
     * LINE Push Messageを送信（ダミー実装）
     * 
     * @param string $lineId
     * @param array $messages
     * @return bool
     */
    private function sendPushMessage(string $lineId, array $messages): bool
    {
        // ダミー実装：実際にはHTTPリクエストを送信
        // 開発環境ではログに記録のみ
        if (app()->environment('local')) {
            Log::info('LINE送信（ダミー）', [
                'line_id' => $lineId,
                'messages' => $messages,
            ]);
            
            // ダミーとして常に成功を返す
            return true;
        }

        // 本番環境では実際にAPIを叩く
        if (empty($this->channelAccessToken)) {
            throw new \Exception('LINE Channel Access Tokenが設定されていません');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->channelAccessToken,
            ])->post($this->apiUrl, [
                'to' => $lineId,
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('LINE API エラー', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('LINE送信例外', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * LIFF経由のURLを生成する。LIFF IDが未設定の場合は通常URLを返す。
     */
    private function buildUrl(string $path): string
    {
        $liffId = Setting::get('liff_id', '');
        if (!empty($liffId)) {
            return 'https://liff.line.me/' . $liffId . '/liff/bridge' . $path;
        }
        return url($path);
    }

    /**
     * ニュース用メッセージを構築
     */
    private function buildNewsMessage(News $news): array
    {
        $url = $this->buildUrl('/news/' . $news->id);
        $sender = $news->creator;
        $senderName = ($sender && $sender->role === 'master_admin') ? '同窓会事務局' : ($sender->full_name ?? '松高.net');

        $text = "【松高.net お知らせ】\n\n";
        $text .= "{$news->title}\n\n";
        $text .= mb_strimwidth($news->body, 0, 150, "...") . "\n\n";
        $text .= "詳細はこちら▶︎ {$url}\n\n";
        $text .= "送信者：{$senderName}";

        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }

    /**
     * イベント用メッセージを構築
     */
    private function buildEventMessage(Event $event): array
    {
        $url = $this->buildUrl('/events/' . $event->id);
        $sender = $event->creator;
        $senderName = ($sender && $sender->role === 'master_admin') ? '同窓会事務局' : ($sender->full_name ?? '松高.net');

        $titlePrefix = "【松高.netからイベントのお知らせ】";
        if ($sender && $sender->role === 'year_admin' && $event->graduation_year) {
            $titlePrefix = "【松高.netから{$event->graduation_year}回生イベントのお知らせ】";
        }

        $text = "{$titlePrefix}\n\n";
        $text .= "📅 {$event->title}\n\n";

        if ($event->event_date) {
            $text .= "日時：" . $event->event_date->format('Y年m月d日 H:i') . "\n";
        }

        if ($event->location) {
            $text .= "場所：{$event->location}\n";
        }

        if ($event->deadline) {
            $text .= "締切：" . $event->deadline->format('Y年m月d日') . "\n";
        }

        $text .= "\n" . mb_strimwidth($event->description, 0, 100, "...") . "\n\n";
        $text .= "詳細・出欠回答はこちら▶︎ {$url}\n\n";
        $text .= "送信者：{$senderName}";

        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }

    /**
     * メール経由で通知を送信（LINE上限超過時のフォールバック）
     * 
     * @param User $user
     * @param News|Event $notifiable
     * @return bool
     */
    private function sendEmailNotification(User $user, $notifiable): bool
    {
        try {
            $subject = '';
            $body = '';
            $url = '';

            if ($notifiable instanceof News) {
                $subject = "【松高.net】お知らせ: {$notifiable->title}";
                $url = url('/news/' . $notifiable->id);
                $body = $this->buildEmailNewsBody($notifiable, $url);
            } elseif ($notifiable instanceof Event) {
                $subject = "【松高.net】イベント: {$notifiable->title}";
                $url = url('/events/' . $notifiable->id);
                $body = $this->buildEmailEventBody($notifiable, $url);
            } else {
                return false;
            }

            // メール送信
            Mail::send('emails.notification', [
                'user' => $user,
                'subject_title' => $notifiable->title,
                'body' => $body,
                'url' => $url,
                'notifiable' => $notifiable,
            ], function ($message) use ($user, $subject) {
                $message->to($user->email)
                        ->subject($subject);
            });

            Log::info('メール送信成功', [
                'user_id' => $user->id,
                'email' => $user->email,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('メール送信エラー', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ニュース用メール本文を構築
     */
    private function buildEmailNewsBody(News $news, string $url): string
    {
        $sender = $news->creator;
        $senderName = ($sender && $sender->role === 'master_admin') ? '同窓会事務局' : ($sender->full_name ?? '松高.net');

        return <<<EOT
お知らせ: {$news->title}

{$news->body}

詳細はこちらをご確認ください:
{$url}

送信者: {$senderName}

※このメールはLINE プッシュ通知の月間上限超過のため、
メールで送信されています。
EOT;
    }

    /**
     * イベント用メール本文を構築
     */
    private function buildEmailEventBody(Event $event, string $url): string
    {
        $sender = $event->creator;
        $senderName = ($sender && $sender->role === 'master_admin') ? '同窓会事務局' : ($sender->full_name ?? '松高.net');
        
        $dateStr = '';
        if ($event->event_date) {
            $dateStr = "日時: {$event->event_date->format('Y年m月d日 H:i')}\n";
        }
        
        $locationStr = '';
        if ($event->location) {
            $locationStr = "場所: {$event->location}\n";
        }
        
        $deadlineStr = '';
        if ($event->deadline) {
            $deadlineStr = "締切: {$event->deadline->format('Y年m月d日')}\n";
        }

        return <<<EOT
イベント情報: {$event->title}

{$dateStr}{$locationStr}{$deadlineStr}
概要:
{$event->description}

詳細・出欠回答はこちらをご確認ください:
{$url}

送信者: {$senderName}

※このメールはLINE プッシュ通知の月間上限超過のため、
メールで送信されています。
EOT;
    }

    /**
     * Flex Messageを使った高度なメッセージ（オプション）
     * 
     * @param Event $event
     * @return array
     */
    private function buildEventFlexMessage(Event $event): array
    {
        return [
            [
                'type' => 'flex',
                'altText' => $event->title,
                'contents' => [
                    'type' => 'bubble',
                    'hero' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $event->title,
                                'weight' => 'bold',
                                'size' => 'xl',
                                'color' => '#2c5f2d',
                            ],
                        ],
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $event->description,
                                'wrap' => true,
                            ],
                        ],
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'button',
                                'action' => [
                                    'type' => 'uri',
                                    'label' => '詳細を見る',
                                    'uri' => url('/events/' . $event->id),
                                ],
                                'style' => 'primary',
                                'color' => '#2c5f2d',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
