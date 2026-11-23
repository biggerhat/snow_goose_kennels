<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Puppy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The "Find…" box: one search across dogs, litters, puppies and contacts. */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';
        $groups = [];

        $dogs = Dog::withTrashed()
            ->where(fn ($w) => $w->whereRaw("name LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("registered_name LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("registry_number LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("microchip LIKE ? ESCAPE '\\'", [$like]))
            ->orderBy('name')->limit(6)->get()
            ->map(fn (Dog $d) => ['label' => $d->name, 'sub' => collect([$d->registered_name, $d->registry_number, $d->is_ours ? null : 'another kennel', $d->trashed() ? 'archived' : null])->filter()->join(' · '), 'href' => route('admin.dogs.edit', $d)]);
        $dogs->isNotEmpty() && $groups[] = ['title' => 'Dogs', 'items' => $dogs->values()];

        $litters = Litter::withTrashed()->with(['sire:id,name', 'dam:id,name'])
            ->where(fn ($w) => $w->whereRaw("name LIKE ? ESCAPE '\\'", [$like])
                ->orWhereHas('sire', fn ($d) => $d->withTrashed()->whereRaw("name LIKE ? ESCAPE '\\'", [$like]))
                ->orWhereHas('dam', fn ($d) => $d->withTrashed()->whereRaw("name LIKE ? ESCAPE '\\'", [$like])))
            ->latest('id')->limit(6)->get()
            ->map(fn (Litter $l) => ['label' => $l->title, 'sub' => ucfirst(str_replace('_', ' ', $l->status->value)).($l->born_on ? ', born '.$l->born_on->format('M j, Y') : ($l->due_on ? ', due '.$l->due_on->format('M j, Y') : '')), 'href' => route('admin.litters.edit', $l)]);
        $litters->isNotEmpty() && $groups[] = ['title' => 'Litters', 'items' => $litters->values()];

        $puppies = Puppy::with('litter.sire:id,name', 'litter.dam:id,name')
            ->where(fn ($w) => $w->whereRaw("name LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("collar LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("microchip LIKE ? ESCAPE '\\'", [$like]))
            ->limit(6)->get()
            ->filter(fn (Puppy $p) => $p->litter)
            ->map(fn (Puppy $p) => ['label' => $p->name ?: ($p->collar ? "{$p->collar} collar puppy" : 'Puppy'), 'sub' => $p->litter->title.' · '.$p->status->label(), 'href' => route('admin.litters.edit', $p->litter)]);
        $puppies->isNotEmpty() && $groups[] = ['title' => 'Puppies', 'items' => $puppies->values()];

        $contacts = Contact::where(fn ($w) => $w->whereRaw("name LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("email LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("phone LIKE ? ESCAPE '\\'", [$like])->orWhereRaw("city LIKE ? ESCAPE '\\'", [$like]))
            ->orderBy('name')->limit(6)->get()
            ->map(fn (Contact $c) => ['label' => $c->name, 'sub' => collect([$c->type->label(), $c->phone, $c->email, $c->city])->filter()->join(' · '), 'href' => route('admin.contacts.edit', $c)]);
        $contacts->isNotEmpty() && $groups[] = ['title' => 'Contacts', 'items' => $contacts->values()];

        return response()->json(['groups' => $groups]);
    }
}
