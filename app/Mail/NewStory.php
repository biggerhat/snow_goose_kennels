<?php

namespace App\Mail;

use App\Models\Testimonial;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the owner a family sent in a story that is waiting for her OK. */
class NewStory extends Mailable
{
    public function __construct(public Testimonial $story) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New story from {$this->story->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-story');
    }
}
