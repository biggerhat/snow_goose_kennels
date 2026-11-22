<?php

namespace App\Support;

use App\Enums\PuppyStatus;
use App\Models\AttentionSnooze;
use App\Models\Document;
use App\Models\Dog;
use App\Models\HealthRecord;
use App\Models\KennelProfile;
use App\Models\Litter;
use App\Models\Puppy;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

/** Things that are due or running out soon: vaccines and treatments, expiring papers, AKC deadlines. */
class Attention
{
    /** @return Collection<int, array{kind: string, title: string, detail: string, date: string, overdue: bool, href: string}> */
    public static function items(int $healthDays = 7, int $documentDays = 30, int $akcDays = 30): Collection
    {
        $snoozed = AttentionSnooze::where('until', '>=', today())->pluck('key')->flip();

        return collect([
            ...self::health($healthDays),
            ...self::documents($documentDays),
            ...self::akc($akcDays),
            ...self::emergencyPlan(),
        ])
            ->map(fn (array $i) => $i + ['key' => self::key($i)])
            ->reject(fn (array $i) => $snoozed->has($i['key']))
            ->sortBy([['overdue', 'desc'], ['date', 'asc']])
            ->values();
    }

    /** A stable id for one reminder, so it can be snoozed. */
    public static function key(array $item): string
    {
        return md5($item['kind'].'|'.$item['title'].'|'.$item['date']);
    }

    /** @return list<array<string, mixed>> */
    private static function health(int $days): array
    {
        $out = [];
        $groups = [];
        $dogGroups = [];

        HealthRecord::with(['recordable' => fn (MorphTo $m) => $m->morphWith([Puppy::class => ['litter']])])->whereNotNull('next_due_on')
            ->where('next_due_on', '<=', today()->addDays($days))->get()
            ->each(function (HealthRecord $r) use (&$out, &$groups, &$dogGroups) {
                $owner = $r->recordable;

                if (! $owner) {
                    return;
                }

                // Already done: a newer entry with the same name exists for the same dog or puppy.
                $later = HealthRecord::where('recordable_type', $r->recordable_type)->where('recordable_id', $r->recordable_id)
                    ->whereRaw('lower(name) = ?', [mb_strtolower(trim($r->name))])->where('given_on', '>', $r->given_on)->exists();

                if ($later) {
                    return;
                }

                if ($owner instanceof Dog) {
                    if ($owner->left_on || ! $owner->is_ours) {
                        return;
                    }

                    // One line per treatment and date, not one per dog.
                    $key = 'dog|'.mb_strtolower(trim($r->name)).'|'.$r->next_due_on->toDateString();
                    $dogGroups[$key] ??= ['dogs' => [], 'record' => $r];
                    $dogGroups[$key]['dogs'][$owner->id] = $owner;

                    return;
                } elseif ($owner instanceof Puppy) {
                    if (in_array($owner->status, [PuppyStatus::Home, PuppyStatus::Kept], true)) {
                        return;
                    }
                    // One line per litter and treatment, not one per puppy.
                    $key = $owner->litter_id.'|'.mb_strtolower(trim($r->name)).'|'.$r->next_due_on->toDateString();
                    $groups[$key] ??= ['count' => 0, 'record' => $r, 'litter' => $owner->litter];
                    $groups[$key]['count']++;

                    return;
                } else {
                    return;
                }

                $out[] = [
                    'kind' => 'health',
                    'title' => "{$r->name} due for {$who}",
                    'detail' => $r->type->label().', last given '.$r->given_on->format('M j, Y'),
                    'date' => $r->next_due_on->toDateString(),
                    'overdue' => $r->next_due_on->lt(today()),
                    'href' => $href,
                ];
            });

        foreach ($dogGroups as $g) {
            $r = $g['record'];
            $dogs = collect($g['dogs'])->values();
            $names = $dogs->pluck('name');
            $who = $dogs->count() <= 3 ? $names->slice(0, -1)->join(', ').($names->count() > 1 ? ' and ' : '').$names->last() : "{$dogs->count()} dogs";
            $out[] = [
                'kind' => 'health',
                'title' => "{$r->name} due for {$who}",
                'detail' => $r->type->label().', last given '.$r->given_on->format('M j, Y').($dogs->count() > 3 ? ' ('.$names->join(', ').')' : ''),
                'date' => $r->next_due_on->toDateString(),
                'overdue' => $r->next_due_on->lt(today()),
                'href' => $dogs->count() === 1 ? route('admin.dogs.edit', $dogs->first()) : route('admin.dogs.index'),
            ];
        }

        foreach ($groups as $g) {
            $r = $g['record'];
            $out[] = [
                'kind' => 'health',
                'title' => "{$r->name} due for ".($g['count'] === 1 ? 'a puppy' : "{$g['count']} puppies")." in {$g['litter']->title}",
                'detail' => $r->type->label().', last given '.$r->given_on->format('M j, Y'),
                'date' => $r->next_due_on->toDateString(),
                'overdue' => $r->next_due_on->lt(today()),
                'href' => route('admin.litters.edit', $g['litter']),
            ];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function documents(int $days): array
    {
        $out = [];

        Document::with('documentable')->whereNotNull('expires_on')->where('expires_on', '<=', today()->addDays($days))->get()
            ->each(function (Document $d) use (&$out) {
                $owner = $d->documentable;

                if (! $owner) {
                    return;
                }

                // Renewed: a newer copy of the same kind of paper runs later.
                $renewed = Document::where('documentable_type', $d->documentable_type)->where('documentable_id', $d->documentable_id)
                    ->where('category', $d->category)->where('expires_on', '>', $d->expires_on)->exists();

                if ($renewed) {
                    return;
                }

                [$who, $href] = match (true) {
                    $owner instanceof Dog => [$owner->name, route('admin.dogs.edit', $owner)],
                    $owner instanceof Litter => [$owner->title, route('admin.litters.edit', $owner)],
                    $owner instanceof Puppy => ['a puppy in '.($owner->litter?->title ?? 'a litter'), route('admin.litters.edit', $owner->litter)],
                    $owner instanceof KennelProfile => ['the kennel', route('admin.records')],
                    default => [$owner->name ?? 'a contact', route('admin.contacts.edit', $owner)],
                };

                $out[] = [
                    'kind' => 'document',
                    'title' => "“{$d->title}” for {$who} ".($d->expires_on->lt(today()) ? 'has expired' : 'is about to expire'),
                    'detail' => $d->expires_on->format('M j, Y'),
                    'date' => $d->expires_on->toDateString(),
                    'overdue' => $d->expires_on->lt(today()),
                    'href' => $href,
                ];
            });

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private static function akc(int $days): array
    {
        return Litter::query()->whereNotNull('born_on')->whereNull('akc_applied_on')
            ->where('born_on', '>=', today()->subYears(2))->where('born_on', '<=', today())->get()
            ->map(function (Litter $l) use ($days) {
                $deadline = $l->born_on->addMonths(6);

                return $deadline->lte(today()->addDays($days)) ? [
                    'kind' => 'akc',
                    'title' => "AKC litter application for {$l->title} ".($deadline->lt(today()) ? 'is past its 6-month deadline' : 'is due'),
                    'detail' => 'Deadline '.$deadline->format('M j, Y'),
                    'date' => $deadline->toDateString(),
                    'overdue' => $deadline->lt(today()),
                    'href' => route('admin.litters.edit', $l),
                ] : null;
            })->filter()->values()->all();
    }

    /** AKC says breeders should have a written emergency plan. Nag gently when there is none or it is old. */
    private static function emergencyPlan(): array
    {
        if (! Dog::where('is_ours', true)->exists()) {
            return [];
        }

        $latest = Document::where('documentable_type', 'kennel')->where('category', 'emergency_plan')->get()
            ->map(fn (Document $d) => $d->issued_on ?? $d->created_at)->max();

        if (! $latest) {
            return [['kind' => 'akc', 'title' => 'Write down your emergency plan', 'detail' => 'AKC asks breeders to have one on paper', 'date' => today()->toDateString(), 'overdue' => false, 'href' => route('admin.records.emergency-plan')]];
        }

        return $latest->lt(today()->subYear()) ? [[
            'kind' => 'akc', 'title' => 'Review your emergency plan', 'detail' => 'Last updated '.$latest->format('M j, Y'),
            'date' => $latest->copy()->addYear()->toDateString(), 'overdue' => true, 'href' => route('admin.records'),
        ]] : [];
    }
}
