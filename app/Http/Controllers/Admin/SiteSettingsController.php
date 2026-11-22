<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Kennel;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/SiteContent', [
            'contact' => Setting::contact(),
            'owner' => collect(Kennel::OWNER_KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k) ?? ''])->all(),
            'sections' => array_map(fn ($s) => [...$s, 'body' => Setting::get($s['key']) ?? ''], SiteContent::SECTIONS),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:200'],
            'location' => ['nullable', 'string', 'max:200'],
            'facebook' => ['nullable', 'url:http,https', 'max:300'],
            'owner_first_name' => ['nullable', 'string', 'max:80'],
            'owner_last_name' => ['nullable', 'string', 'max:80'],
            'owner_address' => ['nullable', 'string', 'max:200'],
            'owner_city' => ['nullable', 'string', 'max:100'],
            'owner_state' => ['nullable', 'string', 'size:2'],
            'owner_zip' => ['nullable', 'regex:/^\d{5}(-\d{4})?$/'],
            'owner_phone' => ['nullable', 'string', 'max:50', 'regex:/^(?:\D*\d){7,}\D*$/'],
            'owner_email' => ['nullable', 'email', 'max:200'],
        ];

        foreach (SiteContent::SECTIONS as $s) {
            $rules[$s['key']] = ['nullable', 'string', 'max:6000'];
        }

        $messages = [
            'owner_state.size' => 'Use the two-letter state code, like ID.',
            'owner_zip.regex' => 'A ZIP code is 5 digits (or 5 digits, a dash and 4 more).',
            'owner_phone.regex' => 'That phone number looks too short. Include the area code.',
        ];

        foreach ($request->validate($rules, $messages) as $key => $value) {
            Setting::put($key, $value);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Website content saved.']);

        return back();
    }
}
