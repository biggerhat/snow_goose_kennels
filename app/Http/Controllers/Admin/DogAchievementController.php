<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AchievementKind;
use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\DogAchievement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DogAchievementController extends Controller
{
    public function store(Request $request, Dog $dog): RedirectResponse
    {
        $dog->achievements()->create($this->validated($request));
        $this->syncTitles($dog);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Title added.']);

        return back();
    }

    public function update(Request $request, DogAchievement $achievement): RedirectResponse
    {
        $achievement->update($this->validated($request));
        $this->syncTitles($achievement->dog);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    public function destroy(DogAchievement $achievement): RedirectResponse
    {
        $dog = $achievement->dog;
        $achievement->delete();
        $this->syncTitles($dog);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Title removed.']);

        return back();
    }

    /** The titles shown after the dog’s name are built from this list, oldest first. */
    private function syncTitles(Dog $dog): void
    {
        $titles = $dog->achievements()->reorder()->orderBy('earned_on')->orderBy('id')->pluck('title')->unique()->join(', ');

        $dog->update(['titles' => $titles === '' ? null : $titles]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:40'],
            'full_name' => ['nullable', 'string', 'max:150'],
            'kind' => ['required', Rule::enum(AchievementKind::class)],
            'earned_on' => ['nullable', 'date', 'before_or_equal:today'],
            'event' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'Enter the letters, like MH or SH.',
        ]);
    }
}
