<?php

namespace App\Support;

use App\Models\Litter;
use Carbon\CarbonInterface;

/**
 * The usual steps for raising a litter, counted from the birth date. Only a starting point:
 * the owner's vet has the final say, and every step can be moved or removed.
 */
class PuppySchedule
{
    /** @return list<array{kind: string, title: string, detail: string|null, due_on: CarbonInterface}> */
    public static function for(Litter $litter): array
    {
        $born = $litter->born_on;

        if (! $born) {
            return [];
        }

        $at = fn (int $weeks) => $born->copy()->addWeeks($weeks);
        $steps = [];

        foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $w) {
            $steps[] = ['kind' => 'weigh', 'title' => "Weigh the puppies ({$w} ".($w === 1 ? 'week' : 'weeks').')', 'detail' => 'Record each weight under Weigh-ins.', 'due_on' => $at($w)];
        }

        foreach ([2, 4, 6, 8] as $w) {
            $steps[] = ['kind' => 'deworm', 'title' => "Deworm the puppies ({$w} weeks)", 'detail' => 'Puppies are usually dewormed every 2 weeks from 2 weeks old. Ask your vet which product and dose.', 'due_on' => $at($w)];
        }

        $steps[] = ['kind' => 'wean', 'title' => 'Start solid food (4 weeks)', 'detail' => 'Puppies usually start weaning around 3 to 4 weeks.', 'due_on' => $at(4)];
        $steps[] = ['kind' => 'akc', 'title' => 'Send in the AKC litter application', 'detail' => 'AKC wants it within 6 months of the birth date.', 'due_on' => $at(6)];
        $steps[] = ['kind' => 'vaccine', 'title' => 'First vaccines (6 to 8 weeks)', 'detail' => 'Ask your vet about the first puppy vaccines and when to repeat them.', 'due_on' => $at(6)];
        $steps[] = ['kind' => 'vet', 'title' => 'Vet check and microchips (7 weeks)', 'detail' => 'A health check before go-home day. Record the microchip number on each puppy.', 'due_on' => $at(7)];
        $steps[] = ['kind' => 'home', 'title' => 'Go-home day', 'detail' => 'Give each family the go-home packet and the papers.', 'due_on' => $litter->go_home_on ?? $at(8)];

        usort($steps, fn ($a, $b) => $a['due_on']->timestamp <=> $b['due_on']->timestamp);

        return $steps;
    }

    /** Adds the steps this litter does not have yet. Returns how many were added. */
    public static function create(Litter $litter): int
    {
        $have = $litter->tasks()->get()->map(fn ($t) => $t->kind.'|'.$t->title)->flip();
        $added = 0;

        foreach (self::for($litter) as $step) {
            if ($have->has($step['kind'].'|'.$step['title'])) {
                continue;
            }

            $litter->tasks()->create($step);
            $added++;
        }

        return $added;
    }
}
