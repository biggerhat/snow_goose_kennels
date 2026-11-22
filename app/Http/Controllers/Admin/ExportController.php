<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Dog;
use App\Models\HealthRecord;
use App\Models\Litter;
use App\Models\Puppy;
use App\Models\WaitlistEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(string $type): StreamedResponse
    {
        [$header, $rows] = match ($type) {
            'dogs' => [
                ['Name', 'Registered name', 'Titles', 'Sex', 'Role', 'Colour', 'Born', 'Registration #', 'AKC DNA profile', 'Microchip', 'Sire', 'Dam', 'Whose dog', 'Owner', 'Came to the kennel', 'Left the kennel', 'Why', 'Public', 'Private notes'],
                Dog::with(['sire', 'dam', 'ownerContact'])->orderBy('name')->get()->map(fn ($d) => [$d->name, $d->registered_name, $d->titles, $d->sex->label(), $d->role->label(), $d->color?->label(), $d->birth_date?->toDateString(), $d->registry_number, $d->dna_profile, $d->microchip, $d->sire?->name, $d->dam?->name, $d->is_ours ? 'Ours' : 'Another kennel', $d->ownerContact?->name, $d->acquired_on?->toDateString(), $d->left_on?->toDateString(), $d->left_reason?->label(), $d->is_public ? 'yes' : 'no', $d->private_notes]),
            ],
            'litters' => [
                ['Litter', 'Sire', 'Sire reg #', 'Dam', 'Dam reg #', 'Bred on', 'How bred', 'Where', 'Handled by', 'Stud fee', 'Stud fee paid', 'Due', 'Born', 'Males', 'Females', 'Not born alive', 'AKC application sent', 'AKC litter number', 'Status'],
                Litter::with(['sire', 'dam', 'puppies'])->orderByDesc('born_on')->get()->map(fn ($l) => [$l->title, $l->sire?->name, $l->sire?->registry_number, $l->dam?->name, $l->dam?->registry_number, $l->bred_on?->toDateString(), $l->breeding_method?->label(), $l->bred_place, $l->bred_handler, $l->stud_fee, $l->stud_fee_paid_on?->toDateString(), $l->due_on?->toDateString(), $l->born_on?->toDateString(), $l->puppies->where('sex.value', 'male')->count(), $l->puppies->where('sex.value', 'female')->count(), $l->stillborn_count, $l->akc_applied_on?->toDateString(), $l->akc_litter_number, $l->status->label()]),
            ],
            'health' => [
                ['For', 'Dog or puppy', 'Kind', 'What', 'Given on', 'Next due', 'Given by', 'Lot', 'Notes'],
                HealthRecord::with('recordable')->orderBy('given_on')->get()->map(fn ($r) => [$r->recordable?->name ?: ($r->recordable?->collar ? $r->recordable->collar.' collar puppy' : 'puppy'), $r->recordable_type === 'dog' ? 'Dog' : 'Puppy', $r->type->label(), $r->name, $r->given_on->toDateString(), $r->next_due_on?->toDateString(), $r->given_by, $r->lot_number, $r->notes]),
            ],
            'documents' => [
                ['Belongs to', 'Kind of record', 'Title', 'What it is', 'File name', 'Dated', 'Runs out'],
                Document::with('documentable')->orderBy('documentable_type')->get()->map(fn ($d) => [$d->documentable?->title ?? $d->documentable?->name ?? $d->documentable?->collar, $d->documentable_type, $d->title, $d->category, $d->original_name, $d->issued_on?->toDateString(), $d->expires_on?->toDateString()]),
            ],
            'puppies' => [
                ['Litter', 'Born', 'Collar', 'Name', 'Sex', 'Colour', 'Birth weight (g)', 'Status', 'Buyer', 'Buyer address', 'Buyer phone', 'Buyer email', 'Sale price', 'Microchip', 'Papers', 'AKC number', 'Papers given', 'Went home', 'Vet check', 'Notes'],
                Puppy::with(['litter.sire', 'litter.dam', 'buyer'])->get()->map(fn ($p) => [$p->litter->title, $p->litter->born_on?->toDateString(), $p->collar, $p->name, $p->sex->label(), $p->color?->label(), $p->birth_weight_g, $p->status->label(), $p->buyer?->name, $p->buyer ? trim("{$p->buyer->address}, {$p->buyer->city}, {$p->buyer->state} {$p->buyer->postal_code}", ' ,') : null, $p->buyer?->phone, $p->buyer?->email, $p->sale_price, $p->microchip, $p->registration_type?->label(), $p->akc_number, $p->papers_given_on?->toDateString(), $p->went_home_on?->toDateString(), $p->vet_checked_on?->toDateString(), $p->notes]),
            ],
            'contacts' => [
                ['Name', 'Type', 'Phone', 'Email', 'Address', 'City', 'State', 'ZIP', 'Notes'],
                Contact::orderBy('name')->get()->map(fn ($c) => [$c->name, $c->type->label(), $c->phone, $c->email, $c->address, $c->city, $c->state, $c->postal_code, $c->notes]),
            ],
            'waitlist' => [
                ['Name', 'Phone', 'Email', 'Litter', 'Prefers', 'Colour', 'Pick order', 'Status', 'Deposit', 'Deposit received', 'Source', 'Added', 'Notes'],
                WaitlistEntry::with(['contact', 'litter.sire', 'litter.dam'])->oldest()->get()->map(fn ($e) => [$e->contact->name, $e->contact->phone, $e->contact->email, $e->litter?->title, $e->preferred_sex?->label(), $e->preferred_color?->label(), $e->pick_order, $e->status->label(), $e->deposit_amount, $e->deposit_paid_on?->toDateString(), $e->source, $e->created_at->toDateString(), $e->notes]),
            ],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
            fputcsv($out, $header);

            foreach ($rows as $row) {
                // Neutralise spreadsheet formula injection from user-entered text.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $row));
            }

            fclose($out);
        }, "snow-goose-$type-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
