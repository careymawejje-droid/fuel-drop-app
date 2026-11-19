<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $contactEmail,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactEmail, $this->contactName)],
            subject: 'FuelDrop contact form message',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-message',
            with: [
                'contactName' => $this->contactName,
                'contactEmail' => $this->contactEmail,
                'messageBody' => $this->messageBody,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
