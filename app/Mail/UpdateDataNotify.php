<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpdateDataNotify extends Mailable
{
    use Queueable, SerializesModels;

    /** 主题
     *
     * @var string|mixed
     */
    public string $subject_content;

    /** 内容
     *
     * @var string|mixed
     */
    public string $main_content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject = '无主题', $content = '')
    {
        $this->subject_content = $subject;
        $this->main_content = $content;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject_content,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'mails.notify',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
