<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $matchType,
        public array $matchedData = [],
        public ?string $approverRole = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【' . Setting::get('site_name', '松.net') . '】新規登録承認依頼',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // 承認画面のURL生成
        $approvalUrl = route('admin.users.index', [
            'approval_status' => 'pending',
            'user_id' => $this->user->id,
        ]);
        
        return new Content(
            view: 'emails.approval-request',
            with: [
                'user' => $this->user,
                'matchType' => $this->matchType,
                'matchedData' => $this->matchedData,
                'approverRole' => $this->approverRole,
                'approvalUrl' => $approvalUrl, // 承認画面URL
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
