<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\HeatCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HeatCycleController extends Controller
{
    public function store(Request $request, Dog $dog): RedirectResponse
    {
        $dog->heatCycles()->create($request->validate([
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'bred_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Heat cycle added.']);

        return back();
    }

    public function destroy(HeatCycle $heatCycle): RedirectResponse
    {
        $heatCycle->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Heat cycle removed.']);

        return back();
    }
}
