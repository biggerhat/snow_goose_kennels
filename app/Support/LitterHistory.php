<?php

namespace App\Support;

use App\Models\Dog;
use App\Models\Litter;

/** How many litters a dam has had and how long ago the last was. Facts for the breeder and her vet, not a rule. */
class LitterHistory
{
    /** @return array{count: int, last_born_on: string|null, months_ago: int|null, text: string} */
    public static function forDam(?Dog $dam, ?Litter $except = null): ?array
    {
        if (! $dam) {
            return null;
        }

        $litters = Litter::withTrashed()->where('dam_id', $dam->id)
            ->when($except?->exists, fn ($q) => $q->where('id', '!=', $except->id))
            ->whereIn('status', ['on_ground', 'all_home'])
            ->orderByDesc('born_on')->get();

        $last = $litters->first(fn (Litter $l) => $l->born_on)?->born_on;
        $months = $last ? (int) floor($last->diffInMonths(today(), true)) : null;
        $count = $litters->count();

        $text = $count === 0
            ? "{$dam->name} has no earlier litters on record."
            : "{$dam->name} has had {$count} earlier ".($count === 1 ? 'litter' : 'litters')
                .($last ? ". The last was born {$last->format('M j, Y')}, about {$months} ".($months === 1 ? 'month' : 'months').' ago.' : '.')
                .' Ask your vet how long to rest her between litters.';

        return ['count' => $count, 'last_born_on' => $last?->toDateString(), 'months_ago' => $months, 'text' => $text];
    }
}
