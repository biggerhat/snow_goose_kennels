<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HealthRecordType;
use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\HealthRecord;
use App\Models\Litter;
use App\Models\Puppy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HealthRecordController extends Controller
{
    public function storeForDog(Request $request, Dog $dog): RedirectResponse
    {
        $dog->healthRecords()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health record added.']);

        return back();
    }

    public function storeForPuppy(Request $request, Puppy $puppy): RedirectResponse
    {
        $puppy->healthRecords()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health record added.']);

        return back();
    }

    /** One entry for every puppy in the litter, such as a deworming day. */
    public function storeForLitter(Request $request, Litter $litter): RedirectResponse
    {
        $data = $this->validated($request);
        $puppies = $litter->puppies;

        abort_if($puppies->isEmpty(), 422, 'Add the puppies first.');

        if ($litter->born_on && $data['given_on'] < $litter->born_on->toDateString()) {
            return back()->withErrors(['given_on' => 'That date is before these puppies were born ('.$litter->born_on->format('M j, Y').').']);
        }

        $puppies->each(fn (Puppy $p) => $p->healthRecords()->create($data));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Recorded for all {$puppies->count()} puppies."]);

        return back();
    }

    public function update(Request $request, HealthRecord $healthRecord): RedirectResponse
    {
        $healthRecord->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    public function destroy(HealthRecord $healthRecord): RedirectResponse
    {
        $healthRecord->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health record removed.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::enum(HealthRecordType::class)],
            'name' => ['required', 'string', 'max:150'],
            'given_on' => ['required', 'date', 'before_or_equal:today'],
            'next_due_on' => ['nullable', 'date', 'after:given_on'],
            'given_by' => ['nullable', 'string', 'max:150'],
            'lot_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'next_due_on.after' => 'The next due date must be after the date it was given.',
            'name.required' => 'Say what it was, like Rabies or Pyrantel.',
        ]);
    }
}
