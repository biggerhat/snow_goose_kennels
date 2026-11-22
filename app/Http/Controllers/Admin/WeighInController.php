<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Litter;
use App\Models\PuppyWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WeighInController extends Controller
{
    /** Record one weigh-in (a date plus a weight in grams per puppy). */
    public function store(Request $request, Litter $litter): RedirectResponse
    {
        $data = $request->validate([
            'weighed_on' => ['required', 'date', 'before_or_equal:today'],
            'weights' => ['required', 'array'],
            'weights.*' => ['nullable', 'integer', 'min:1', 'max:60000'],
        ]);

        if ($litter->born_on && Carbon::parse($data['weighed_on'])->lt($litter->born_on)) {
            throw ValidationException::withMessages(['weighed_on' => 'That date is before these puppies were born ('.$litter->born_on->format('M j, Y').').']);
        }

        $ids = $litter->puppies()->pluck('id')->all();
        $saved = 0;

        foreach ($data['weights'] as $puppyId => $grams) {
            if ($grams === null || ! in_array((int) $puppyId, $ids, true)) {
                continue;
            }

            PuppyWeight::updateOrCreate(
                ['puppy_id' => $puppyId, 'weighed_on' => $data['weighed_on']],
                ['weight_g' => $grams],
            );
            $saved++;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Saved $saved weight(s)."]);

        return back();
    }

    public function destroy(Litter $litter, string $date): RedirectResponse
    {
        PuppyWeight::whereIn('puppy_id', $litter->puppies()->select('id'))->whereDate('weighed_on', $date)->delete();

        return back();
    }
}
