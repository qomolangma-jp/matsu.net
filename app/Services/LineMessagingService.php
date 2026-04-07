<?php

namespace App\Services;

use App\Models\User;
use App\Models\News;
use App\Models\Event;
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
     * ニュース用メッセージを構築
     * 
     * @param News $news
     * @return array
     */
    private function buildNewsMessage(News $news): array
    {
        $text = "【松.net お知らせ】\n\n";
        $text .= "{$news->title}\n\n";
        $text .= $news->body;

        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }

    /**
     * イベント用メッセージを構築
     * 
     * @param Event $event
     * @return array
     */
    private function buildEventMessage(Event $event): array
    {
        $text = "【松.net イベントのお知らせ】\n\n";
        $text .= "📅 {$event->title}\n\n";
        
        if ($event->event_date) {
            $text .= "日時：" . $event->event_date->format('Y年m月d日 H:i') . "\n";
        }
        
        if ($event->event_location) {
            $text .= "場所：{$event->event_location}\n";
        }
        
        if ($event->registration_deadline) {
            $text .= "締切：" . $event->registration_deadline->format('Y年m月d日') . "\n";
        }
        
        $text .= "\n{$event->description}\n\n";
        $text .= "詳細・出欠回答はこちら▶︎ " . url('/events/' . $event->id);

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
