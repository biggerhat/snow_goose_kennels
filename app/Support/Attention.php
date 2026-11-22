<?php

namespace App\Support;

use App\Enums\PuppyStatus;
use App\Models\Document;
use App\Models\Dog;
use App\Models\HealthRecord;
use App\Models\Litter;
use App\Models\Puppy;
use Illuminate\Support\Collection;

/** Things that are due or running out soon: vaccines and treatments, expiring papers, AKC deadlines. */
class Attention
{
    /** @return Collection<int, array{kind: string, title: string, detail: string, date: string, overdue: bool, href: string}> */
    public static function items(int $healthDays = 7, int $documentDays = 30, int $akcDays = 30): Collection
    {
        return collect([
            ...self::health($healthDays),
            ...self::documents($documentDays),
            ...self::akc($akcDays),
        ])->sortBy('date')->values();
    }

    /** @return list<array<string, mixed>> */
    private static function health(int $days): array
    {
        $out = [];

        HealthRecord::with('recordable')->whereNotNull('next_due_on')
            ->where('next_due_on', '<=', today()->addDays($days))->get()
            ->each(function (HealthRecord $r) use (&$out) {
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
                    $who = $owner->name;
                    $href = route('admin.dogs.edit', $owner);
                } elseif ($owner instanceof Puppy) {
                    if (in_array($owner->status, [PuppyStatus::Home, PuppyStatus::Kept], true)) {
                        return;
                    }
                    $who = ($owner->name ?: ($owner->collar ? "{$owner->collar} collar puppy" : 'a puppy')).' ('.($owner->litter?->title ?? 'litter').')';
                    $href = route('admin.litters.edit', $owner->litter);
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
}
