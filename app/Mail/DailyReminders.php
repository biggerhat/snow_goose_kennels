<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class DailyReminders extends Mailable
{
    public function __construct(
        public Collection $due,
        public Collection $goingHome,
        public Collection $unanswered,
        public Collection $attention = new Collection,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kennel reminders');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.daily-reminders');
    }
}
