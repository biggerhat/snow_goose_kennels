<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\HealthTest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HealthTestController extends Controller
{
    public function store(Request $request, Dog $dog): RedirectResponse
    {
        $dog->healthTests()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health test added.']);

        return back();
    }

    public function update(Request $request, HealthTest $healthTest): RedirectResponse
    {
        $healthTest->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    public function destroy(HealthTest $healthTest): RedirectResponse
    {
        $healthTest->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health test removed.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'result' => ['required', 'string', 'max:100'],
            'tested_on' => ['nullable', 'date', 'before_or_equal:today'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'verification_url' => ['nullable', 'url:http,https', 'max:500'],
            'is_public' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
