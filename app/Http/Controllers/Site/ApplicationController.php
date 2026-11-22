<?php

namespace App\Http\Controllers\Site;

use App\Enums\CoatColor;
use App\Enums\ContactType;
use App\Enums\DogSex;
use App\Enums\LitterStatus;
use App\Enums\WaitlistStatus;
use App\Http\Controllers\Controller;
use App\Mail\NewApplication;
use App\Models\Contact;
use App\Models\Litter;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('public/Apply', [
            'litters' => Litter::query()
                ->where('is_public', true)
                ->whereIn('status', [LitterStatus::Planned, LitterStatus::Expected, LitterStatus::OnGround])
                ->with(['sire:id,name', 'dam:id,name'])
                ->orderBy('due_on')->get()
                ->map(fn (Litter $l) => ['id' => $l->id, 'title' => $l->title]),
            'presetLitter' => $request->integer('litter') ?: null,
            'submitted' => $request->session()->get('application_submitted', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real people never see or fill this field.
        if (filled($request->input('website'))) {
            return to_route('site.apply')->with('application_submitted', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:200', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'litter_id' => ['nullable', Rule::exists('litters', 'id')->where('is_public', true)],
            'preferred_sex' => ['nullable', Rule::enum(DogSex::class)],
            'preferred_color' => ['nullable', Rule::enum(CoatColor::class)],
            'home' => ['nullable', 'string', 'max:1500'],
            'experience' => ['nullable', 'string', 'max:1500'],
            'purpose' => ['nullable', 'string', 'max:1500'],
            'heard' => ['nullable', 'string', 'max:300'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        $contact = $data['email'] ?? null ? Contact::firstWhere('email', $data['email']) : null;
        $contact ??= Contact::create([
            'name' => $data['name'],
            'type' => ContactType::Buyer,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
        ]);

        $answers = array_filter([
            'Home and family' => $data['home'] ?? null,
            'Experience with dogs' => $data['experience'] ?? null,
            'What they want a Lab for' => $data['purpose'] ?? null,
            'How they heard about us' => $data['heard'] ?? null,
            'Message' => $data['message'] ?? null,
        ]);

        $entry = WaitlistEntry::create([
            'contact_id' => $contact->id,
            'litter_id' => $data['litter_id'] ?? null,
            'preferred_sex' => $data['preferred_sex'] ?? null,
            'preferred_color' => $data['preferred_color'] ?? null,
            'status' => WaitlistStatus::New,
            'source' => 'website',
            'application' => $answers,
        ]);

        $contact->contactNotes()->create(['body' => 'Applied through the website.', 'noted_at' => now()]);

        try {
            Mail::to(User::pluck('email')->all())->send(new NewApplication($entry->load('contact', 'litter')));
        } catch (\Throwable $e) {
            report($e); // never lose an application because email failed
        }

        return to_route('site.apply')->with('application_submitted', true);
    }
}
