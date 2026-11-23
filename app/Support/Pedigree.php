<?php

namespace App\Support;

use App\Models\Dog;

/** A dog's family tree, built a generation at a time (one query per generation). */
class Pedigree
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Dog $dog, int $generations = 3, bool $public = false): array
    {
        $byId = [$dog->id => $dog];
        $level = [$dog];

        for ($g = 0; $g < $generations; $g++) {
            $ids = collect($level)->flatMap(fn (Dog $d) => [$d->sire_id, $d->dam_id])->filter()->unique()
                ->reject(fn ($id) => isset($byId[$id]))->values();

            if ($ids->isEmpty()) {
                break;
            }

            $level = Dog::withTrashed()->whereIn('id', $ids)->get()->each(function (Dog $d) use (&$byId) {
                $byId[$d->id] = $d;
            })->all();
        }

        $node = function (?int $id, int $depth) use (&$node, $byId, $generations, $public): ?array {
            $d = $id ? ($byId[$id] ?? null) : null;

            if (! $d) {
                return null;
            }

            $out = [
                'name' => $d->name,
                'registered_name' => $d->registered_name,
                'titles' => $d->titles,
                'color' => $d->color?->value,
                'sex' => $d->sex->value,
                'slug' => $d->is_public && $d->is_ours ? $d->slug : null,
            ];

            if (! $public) {
                $out += ['id' => $d->id, 'registry_number' => $d->registry_number, 'is_ours' => $d->is_ours, 'admin_slug' => $d->slug];
            }

            if ($depth < $generations) {
                $out['sire'] = $node($d->sire_id, $depth + 1);
                $out['dam'] = $node($d->dam_id, $depth + 1);
            }

            return $out;
        };

        return $node($dog->id, 0) ?? [];
    }

    /** Whether $candidate already appears among the ancestors of $dogId (stops a dog becoming its own grandparent). */
    public static function hasAncestor(int $dogId, int $candidate, int $depth = 8): bool
    {
        $ids = [$dogId];

        for ($i = 0; $i < $depth && $ids; $i++) {
            $parents = Dog::withTrashed()->whereIn('id', $ids)->get(['sire_id', 'dam_id'])->flatMap(fn ($d) => [$d->sire_id, $d->dam_id])->filter()->unique()->values()->all();

            if (in_array($candidate, $parents, true)) {
                return true;
            }

            $ids = $parents;
        }

        return false;
    }
}
