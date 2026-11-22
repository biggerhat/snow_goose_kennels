<?php

namespace App\Mail;

use App\Models\WaitlistEntry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewApplication extends Mailable
{
    public function __construct(public WaitlistEntry $entry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New puppy application: {$this->entry->contact->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-application');
    }
}
