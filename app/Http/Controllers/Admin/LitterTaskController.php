<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Litter;
use App\Models\LitterTask;
use App\Support\PuppySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LitterTaskController extends Controller
{
    /** Builds the usual puppy schedule from the birth date. */
    public function generate(Litter $litter): RedirectResponse
    {
        if (! $litter->born_on) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Add the birth date on the Details tab first. The schedule is counted from it.']);

            return back();
        }

        $added = PuppySchedule::create($litter);

        Inertia::flash('toast', ['type' => 'success', 'message' => $added ? "Added {$added} steps to the puppy schedule." : 'The schedule is already complete.']);

        return back();
    }

    public function store(Request $request, Litter $litter): RedirectResponse
    {
        $litter->tasks()->create($request->validate([
            'title' => ['required', 'string', 'max:150'],
            'detail' => ['nullable', 'string', 'max:400'],
            'due_on' => ['required', 'date'],
        ], ['title.required' => 'Say what needs doing, like “Vet visit”.', 'due_on.required' => 'Pick the day it is due.']) + ['kind' => 'custom']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Step added.']);

        return back();
    }

    /** Ticking a step off, un-ticking it, or moving its date. */
    public function update(Request $request, LitterTask $task): RedirectResponse
    {
        $data = $request->validate([
            'done' => ['sometimes', 'boolean'],
            'due_on' => ['sometimes', 'date'],
            'title' => ['sometimes', 'string', 'max:150'],
        ]);

        if (array_key_exists('done', $data)) {
            $task->done_on = $data['done'] ? today() : null;
            unset($data['done']);
        }

        $task->fill($data)->save();

        return back();
    }

    public function destroy(LitterTask $task): RedirectResponse
    {
        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Step removed.']);

        return back();
    }
}
