@component('mail::message')
# {{ $subject_title }}

@if($notifiable instanceof App\Models\News)
{{ $notifiable->body }}
@elseif($notifiable instanceof App\Models\Event)
**日時:** {{ $notifiable->event_date?->format('Y年m月d日 H:i') ?? '未定' }}

@if($notifiable->location)
**場所:** {{ $notifiable->location }}
@endif

@if($notifiable->deadline)
**締切:** {{ $notifiable->deadline->format('Y年m月d日') }}
@endif

**概要:**

{{ $notifiable->description }}
@endif

@component('mail::button', ['url' => $url, 'color' => 'primary'])
詳細を見る
@endcomponent

※ このメールは LINE プッシュ通知の月間上限超過のため、
メールで送信されています。

Thanks,
{{ config('app.name') }}
@endcomponent
