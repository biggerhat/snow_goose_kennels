<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttentionSnooze;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AttentionSnoozeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'size:32'],
            'days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        AttentionSnooze::updateOrCreate(['key' => $data['key']], ['until' => today()->addDays($data['days'] ?? 7)]);
        Cache::forget('nav.attention');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Okay, we will remind you again in '.($data['days'] ?? 7).' days.']);

        return back();
    }
}
