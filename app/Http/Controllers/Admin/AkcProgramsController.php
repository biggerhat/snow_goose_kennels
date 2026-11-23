<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AkcProgramsController extends Controller
{
    /** The things only she knows: years in AKC events, club membership, good standing, last course. */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'club' => ['boolean'],
            'standing' => ['boolean'],
            'education' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'years.integer' => 'Enter the number of years as a whole number, like 5.',
            'education.before_or_equal' => 'That date is in the future. Use the day you finished the course.',
        ]);

        Setting::put('akc_years_in_events', isset($data['years']) ? (string) $data['years'] : null);
        Setting::put('akc_club_member', ($data['club'] ?? false) ? '1' : null);
        Setting::put('akc_good_standing', ($data['standing'] ?? false) ? '1' : null);
        Setting::put('akc_education_done_on', $data['education'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }
}
