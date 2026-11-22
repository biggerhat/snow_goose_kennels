<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q'));
        $type = $request->query('type');

        return Inertia::render('admin/contacts/Index', [
            'contacts' => Contact::query()
                ->when($search, fn ($q) => $q->where(fn ($q) => $q
                    ->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")))
                ->when(ContactType::tryFrom((string) $type), fn ($q, $t) => $q->where('type', $t))
                ->withCount('waitlistEntries')
                ->orderBy('name')->get(),
            'filters' => ['q' => $search, 'type' => $type],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/contacts/Edit', ['contact' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $contact = Contact::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$contact->name} added."]);

        return to_route('admin.contacts.edit', $contact);
    }

    public function edit(Contact $contact): Response
    {
        $contact->load(['contactNotes', 'waitlistEntries.litter.sire:id,name', 'waitlistEntries.litter.dam:id,name', 'puppies.litter']);
        $contact->waitlistEntries->each(fn ($e) => $e->litter?->append('title'));
        $contact->puppies->each(fn ($p) => $p->litter?->append('title'));

        return Inertia::render('admin/contacts/Edit', ['contact' => $contact]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $contact->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contact removed.']);

        return to_route('admin.contacts.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(ContactType::class)],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^(?:\D*\d){7,}\D*$/'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ], [
            'phone.regex' => 'That phone number looks too short. Include the area code, like (208) 555-0123.',
        ]);
    }
}
