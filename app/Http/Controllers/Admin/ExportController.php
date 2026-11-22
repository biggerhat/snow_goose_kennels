<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Puppy;
use App\Models\WaitlistEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __invoke(string $type): StreamedResponse
    {
        [$header, $rows] = match ($type) {
            'dogs' => [
                ['Name', 'Registered name', 'Sex', 'Role', 'Colour', 'Born', 'Registration #', 'Microchip', 'Public', 'Private notes'],
                Dog::orderBy('name')->get()->map(fn ($d) => [$d->name, $d->registered_name, $d->sex->label(), $d->role->label(), $d->color?->label(), $d->birth_date?->toDateString(), $d->registry_number, $d->microchip, $d->is_public ? 'yes' : 'no', $d->private_notes]),
            ],
            'puppies' => [
                ['Litter', 'Born', 'Collar', 'Name', 'Sex', 'Colour', 'Status', 'Buyer', 'Sale price', 'Notes'],
                Puppy::with(['litter.sire', 'litter.dam', 'buyer'])->get()->map(fn ($p) => [$p->litter->title, $p->litter->born_on?->toDateString(), $p->collar, $p->name, $p->sex->label(), $p->color?->label(), $p->status->label(), $p->buyer?->name, $p->sale_price, $p->notes]),
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
