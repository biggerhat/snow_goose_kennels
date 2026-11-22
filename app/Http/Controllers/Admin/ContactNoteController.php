<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    public function store(Request $request, Contact $contact): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'noted_at' => ['nullable', 'date'],
        ]);

        $contact->contactNotes()->create([
            'body' => $data['body'],
            'noted_at' => $data['noted_at'] ?? now(),
        ]);

        return back();
    }

    public function destroy(ContactNote $contactNote): RedirectResponse
    {
        $contactNote->delete();

        return back();
    }
}
