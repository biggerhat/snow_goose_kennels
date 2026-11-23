<?php

namespace App\Support;

use App\Models\Dog;

/**
 * Wright's coefficient of inbreeding for a planned mating, worked out from the family trees on file.
 *
 * It only knows the ancestors that have been entered, so a short pedigree gives a low number even if the
 * parents are in fact related. For comparison: first cousins 6.25%, half siblings 12.5%, full siblings 25%.
 */
class Inbreeding
{
    /** @var array<int, array{sire: int|null, dam: int|null}> */
    private array $parents = [];

    /** @var array<int, int> */
    private array $depth = [];

    /** @var array<string, float> */
    private array $memo = [];

    /** @var array<int, string> */
    private array $names = [];

    /** @return array{coefficient: float, percent: float, generations: int, common: list<string>} */
    public static function forMating(Dog $sire, Dog $dam): array
    {
        $calc = new self;
        $calc->load([$sire->id, $dam->id]);

        $sireAncestors = $calc->ancestors($sire->id);
        $damAncestors = $calc->ancestors($dam->id);
        $common = array_values(array_intersect($sireAncestors, $damAncestors));

        return [
            'coefficient' => $f = 0.5 * $calc->rel($sire->id, $dam->id),
            'percent' => round($f * 100, 2),
            'generations' => min($calc->depthOf($sire->id), $calc->depthOf($dam->id)) + 1,
            'common' => array_values(array_unique(array_map(fn ($id) => $calc->names[$id] ?? '?', $common))),
        ];
    }

    /** Pull in every known ancestor, one generation per query. */
    private function load(array $ids): void
    {
        for ($i = 0; $i < 12 && $ids; $i++) {
            $ids = array_values(array_diff($ids, array_keys($this->parents)));

            if (! $ids) {
                break;
            }

            $next = [];

            foreach (Dog::withTrashed()->whereIn('id', $ids)->get(['id', 'name', 'sire_id', 'dam_id']) as $d) {
                $this->parents[$d->id] = ['sire' => $d->sire_id, 'dam' => $d->dam_id];
                $this->names[$d->id] = $d->name;
                array_push($next, ...array_filter([$d->sire_id, $d->dam_id]));
            }

            $ids = array_unique($next);
        }
    }

    /** @return list<int> */
    private function ancestors(int $id): array
    {
        $out = [];
        $stack = array_filter([$this->parents[$id]['sire'] ?? null, $this->parents[$id]['dam'] ?? null]);

        while ($stack) {
            $a = array_pop($stack);

            if (isset($out[$a])) {
                continue;
            }

            $out[$a] = $a;
            array_push($stack, ...array_filter([$this->parents[$a]['sire'] ?? null, $this->parents[$a]['dam'] ?? null]));
        }

        return array_values($out);
    }

    /** Generations of known ancestors above this dog (0 when no parent is known). */
    private function depthOf(?int $id): int
    {
        if (! $id || ! isset($this->parents[$id])) {
            return -1;
        }

        return $this->depth[$id] ??= (function () use ($id) {
            $p = $this->parents[$id];

            return ($p['sire'] || $p['dam']) ? 1 + max($this->depthOf($p['sire']), $this->depthOf($p['dam'])) : 0;
        })();
    }

    /** Additive relationship between two dogs: 1 + F for the same dog, otherwise how much of their genes they share. */
    private function rel(?int $a, ?int $b): float
    {
        if (! $a || ! $b) {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0 + $this->inbreeding($a);
        }

        if ($this->depthOf($a) < $this->depthOf($b)) {
            [$a, $b] = [$b, $a];
        }

        $key = $a < $b ? "$a:$b" : "$b:$a";

        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $p = $this->parents[$a] ?? ['sire' => null, 'dam' => null];

        return $this->memo[$key] = 0.5 * ($this->rel($p['sire'], $b) + $this->rel($p['dam'], $b));
    }

    private function inbreeding(int $id): float
    {
        $p = $this->parents[$id] ?? ['sire' => null, 'dam' => null];

        return ($p['sire'] && $p['dam']) ? 0.5 * $this->rel($p['sire'], $p['dam']) : 0.0;
    }
}
