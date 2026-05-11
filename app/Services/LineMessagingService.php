<?php

namespace App\Services;

use App\Models\User;
use App\Models\News;
use App\Models\Event;
use App\Models\LineNotificationLog;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LineMessagingService
{
    private ?string $channelAccessToken;
    private string $apiUrl = 'https://api.line.me/v2/bot/message/push';

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
     *
     * @param News|Event $notifiable
     * @param bool $resendAll true=全員へ再送（既送信含む）  false=未送信のみ
     * @return array ['success_count', 'failure_count', 'errors', 'target_count']
     */
    public function sendNotification($notifiable, bool $resendAll = false): array
    {
        // 対象ユーザーのベースクエリ（承認済み・LINE ID あり）
        $usersQuery = User::approved()->whereNotNull('line_id');

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
            ];
        }

        $messages = $notifiable instanceof News
            ? $this->buildNewsMessage($notifiable)
            : $this->buildEventMessage($notifiable);

        $successCount = 0;
        $failureCount = 0;
        $errors       = [];

        foreach ($users as $user) {
            try {
                $result = $this->sendPushMessage($user->line_id, $messages);
                if ($result) {
                    $successCount++;
                    // 送信ログを記録
                    LineNotificationLog::create([
                        'notifiable_type' => get_class($notifiable),
                        'notifiable_id'   => $notifiable->id,
                        'user_id'         => $user->id,
                    ]);
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                $errors[] = "{$user->full_name}: {$e->getMessage()}";
                Log::error('LINE送信エラー', [
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
            return 'https://liff.line.me/' . $liffId . '?liff.state=' . urlencode($path);
        }
        return url($path);
    }

    /**
     * ニュース用メッセージを構築
     */
    private function buildNewsMessage(News $news): array
    {
        $url  = $this->buildUrl('/news/' . $news->id);
        $text = "【松.net お知らせ】\n\n";
        $text .= "{$news->title}\n\n";
        $text .= $news->body;
        $text .= "\n\n詳細はこちら▶︎ {$url}";

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
        $url  = $this->buildUrl('/events/' . $event->id);
        $text = "【松.net イベントのお知らせ】\n\n";
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

        $text .= "\n{$event->description}\n\n";
        $text .= "詳細・出欠回答はこちら▶︎ {$url}";

        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
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
