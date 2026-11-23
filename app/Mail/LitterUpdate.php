<?php

namespace App\Mail;

use App\Models\Contact;
use App\Support\Kennel;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A news email from the kennel to one person waiting for a litter. */
class LitterUpdate extends Mailable
{
    public function __construct(public Contact $contact, public string $subjectLine, public string $messageBody, public string $litterTitle) {}

    public function envelope(): Envelope
    {
        $email = Kennel::owner()['email'];

        return new Envelope(subject: $this->subjectLine, replyTo: $email ? [$email] : []);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.litter-update', with: ['owner' => Kennel::owner(), 'first' => explode(' ', trim($this->contact->name))[0], 'messageBody' => preg_replace('/\r?\n/', "  \n", $this->messageBody)]);
    }
}
