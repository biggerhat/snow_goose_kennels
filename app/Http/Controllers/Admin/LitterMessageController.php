<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WaitlistStatus;
use App\Http\Controllers\Controller;
use App\Mail\LitterUpdate;
use App\Models\Litter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/** News for the people waiting on a litter, sent one email each so nobody sees anyone else's address. */
class LitterMessageController extends Controller
{
    public function store(Request $request, Litter $litter): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'entry_ids' => ['required', 'array', 'min:1', 'max:100'],
            'entry_ids.*' => ['integer'],
        ], [
            'subject.required' => 'Give the email a short subject, like “The puppies are here!”.',
            'body.required' => 'Write what you want to tell them.',
            'entry_ids.required' => 'Tick at least one person to send it to.',
            'entry_ids.min' => 'Tick at least one person to send it to.',
        ]);

        $entries = $litter->waitlistEntries()->with('contact')->whereIn('id', $data['entry_ids'])
            ->whereNotIn('status', [WaitlistStatus::Placed, WaitlistStatus::Passed])->get()
            ->filter(fn ($e) => filled($e->contact?->email))
            ->unique('contact_id');

        $sent = 0;
        $failed = [];

        foreach ($entries as $entry) {
            try {
                Mail::to($entry->contact->email)->send(new LitterUpdate($entry->contact, $data['subject'], $data['body'], $litter->title));
                $entry->contact->contactNotes()->create(['body' => "Emailed an update: “{$data['subject']}”", 'noted_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $entry->contact->name;
            }
        }

        if ($sent) {
            $litter->messages()->create(['subject' => $data['subject'], 'body' => $data['body'], 'recipient_count' => $sent, 'sent_at' => now()]);
        }

        Inertia::flash('toast', $failed
            ? ['type' => 'error', 'message' => "Sent to {$sent}, but these did not go through: ".implode(', ', $failed).'. Check the email address, or ask whoever set up the site to check email settings.']
            : ['type' => 'success', 'message' => $sent ? "Sent to {$sent} ".($sent === 1 ? 'person' : 'people').'.' : 'Nobody to send to: the people ticked have no email address.']);

        return back();
    }
}
