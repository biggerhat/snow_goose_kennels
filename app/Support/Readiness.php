<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Dog;
use App\Models\Litter;

/**
 * "If AKC asked to see my records tomorrow, what would I be embarrassed about?"
 * A plain list built from the same checks as the dog and litter pages.
 */
class Readiness
{
    /** @return array{items: list<array<string, mixed>>, todo: int} */
    public static function report(): array
    {
        $items = [];

        // Dogs still here, or gone for less than 5 years.
        $dogs = Dog::withTrashed()->where('is_ours', true)->get()->filter(fn (Dog $d) => ! $d->left_on || $d->left_on->gt(today()->subYears(5)));
        $incomplete = [];
        foreach ($dogs as $dog) {
            $r = AkcChecks::forDog($dog);
            if ($r['todo'] + $r['warn'] > 0) {
                $incomplete[] = ['text' => "{$dog->name}: ".collect($r['items'])->whereIn('status', ['todo', 'warn'])->pluck('title')->join('; '), 'href' => route('admin.dogs.edit', $dog)];
            }
        }
        $items[] = $incomplete
            ? ['key' => 'dogs', 'status' => 'todo', 'title' => count($incomplete).' of '.$dogs->count().' dog records are incomplete', 'details' => $incomplete]
            : ['key' => 'dogs', 'status' => 'ok', 'title' => $dogs->isEmpty() ? 'No dogs yet' : 'Every dog record is complete', 'details' => []];

        // Litters from the last 5 years.
        $litters = Litter::withTrashed()->with(['sire', 'dam', 'puppies.buyer'])->whereNotNull('born_on')->where('born_on', '>', today()->subYears(5))->get();
        $open = [];
        foreach ($litters as $litter) {
            $r = AkcChecks::forLitter($litter);
            if ($r['todo'] + $r['warn'] > 0) {
                $open[] = ['text' => "{$litter->title}: ".collect($r['items'])->whereIn('status', ['todo', 'warn'])->pluck('title')->join('; '), 'href' => route('admin.litters.edit', $litter)];
            }
        }
        $items[] = $open
            ? ['key' => 'litters', 'status' => 'todo', 'title' => count($open).' of '.$litters->count().' litter records are incomplete', 'details' => $open]
            : ['key' => 'litters', 'status' => 'ok', 'title' => $litters->isEmpty() ? 'No litters in the last 5 years' : 'Every litter record is complete', 'details' => []];

        // Emergency plan
        $plan = Document::where('documentable_type', 'kennel')->where('category', 'emergency_plan')->get()->map(fn (Document $d) => $d->issued_on ?? $d->created_at)->max();
        $items[] = match (true) {
            ! $plan => ['key' => 'plan', 'status' => 'todo', 'title' => 'No written emergency plan on file', 'details' => [['text' => 'Print the worksheet, fill it in, and keep a copy under Kennel papers.', 'href' => route('admin.records.emergency-plan')]]],
            $plan->lt(today()->subYear()) => ['key' => 'plan', 'status' => 'todo', 'title' => 'The emergency plan has not been reviewed in over a year', 'details' => [['text' => 'Last updated '.$plan->format('M j, Y').'. Review it and add the new copy under Kennel papers.', 'href' => route('admin.records')]]],
            default => ['key' => 'plan', 'status' => 'ok', 'title' => 'Emergency plan on file and up to date', 'details' => []],
        };

        // Overdue health care
        $overdue = Attention::items(0)->where('kind', 'health')->where('overdue', true);
        $items[] = $overdue->isNotEmpty()
            ? ['key' => 'health', 'status' => 'todo', 'title' => $overdue->count().' vaccines or treatments are overdue', 'details' => $overdue->map(fn ($a) => ['text' => $a['title'], 'href' => $a['href']])->values()->all()]
            : ['key' => 'health', 'status' => 'ok', 'title' => 'No vaccines or treatments overdue', 'details' => []];

        // Who the owner is
        $items[] = Kennel::ownerComplete()
            ? ['key' => 'owner', 'status' => 'ok', 'title' => 'Kennel owner details filled in', 'details' => []]
            : ['key' => 'owner', 'status' => 'todo', 'title' => 'Kennel owner details are not filled in', 'details' => [['text' => 'Add your name and mailing address. They are used on AKC forms.', 'href' => route('admin.site.edit')]]];

        return ['items' => $items, 'todo' => count(array_filter($items, fn ($i) => $i['status'] === 'todo'))];
    }
}
