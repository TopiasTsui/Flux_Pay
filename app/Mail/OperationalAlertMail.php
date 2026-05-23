<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperationalAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $alertTitle,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[FluxPay Alert] '.$this->alertTitle);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<pre style="font-family:monospace;white-space:pre-wrap">'
                .e($this->body)
                .'</pre>',
        );
    }
}
