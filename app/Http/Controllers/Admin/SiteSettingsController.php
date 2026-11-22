<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        ];

        foreach (SiteContent::SECTIONS as $s) {
            $rules[$s['key']] = ['nullable', 'string', 'max:6000'];
        }

        foreach ($request->validate($rules) as $key => $value) {
            Setting::put($key, $value);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Website content saved.']);

        return back();
    }
}
