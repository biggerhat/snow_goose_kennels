<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Litter;
use App\Models\Puppy;
use App\Models\Setting;
use App\Models\WaitlistEntry;
use App\Support\Kennel;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;

/**
 * Printable papers for buyers and studs, filled in from what is already entered.
 * They only contain wording the owner wrote herself (see SiteContent::PAPERS and SECTIONS).
 */
class PaperworkController extends Controller
{
    public function packet(Puppy $puppy): View
    {
        $puppy->load(['litter.sire', 'litter.dam', 'buyer', 'healthRecords', 'weights']);

        return view('records.puppy-packet', [
            'puppy' => $puppy,
            'litter' => $puppy->litter,
            'owner' => Kennel::owner(),
            'contact' => Setting::contact(),
            'food' => Setting::get('packet_food'),
            'care' => Setting::get('packet_care'),
            'vet' => Setting::get('packet_vet'),
            'back' => route('admin.litters.edit', $puppy->litter).'#puppies',
            'backLabel' => 'Back to the litter',
        ]);
    }

    public function agreement(Puppy $puppy): View
    {
        $puppy->load(['litter.sire', 'litter.dam', 'buyer']);
        $entry = WaitlistEntry::where('puppy_id', $puppy->id)->latest('id')->first()
            ?? ($puppy->contact_id ? WaitlistEntry::where('contact_id', $puppy->contact_id)->where('litter_id', $puppy->litter_id)->latest('id')->first() : null);
        $policies = collect(SiteContent::filled())->whereIn('key', ['deposit_policy', 'refund_policy', 'health_guarantee', 'spay_neuter_policy', 'takeback_policy'])->values();

        return view('records.sales-agreement', [
            'puppy' => $puppy,
            'litter' => $puppy->litter,
            'owner' => Kennel::owner(),
            'entry' => $entry,
            'policies' => $policies,
            'extra' => Setting::get('contract_extra'),
            'payment' => Setting::get('payment_instructions'),
            'back' => route('admin.litters.edit', $puppy->litter).'#puppies',
            'backLabel' => 'Back to the litter',
        ]);
    }

    public function receipt(WaitlistEntry $waitlist): View
    {
        $waitlist->load(['contact', 'litter', 'puppy']);

        return view('records.deposit-receipt', [
            'entry' => $waitlist,
            'owner' => Kennel::owner(),
            'deposit_policy' => Setting::get('deposit_policy'),
            'payment' => Setting::get('payment_instructions'),
            'back' => route('admin.waitlist.edit', $waitlist),
            'backLabel' => 'Back to the waitlist entry',
        ]);
    }

    public function stud(Litter $litter): View
    {
        $litter->load(['sire.ownerContact', 'dam.ownerContact']);

        return view('records.stud-agreement', [
            'litter' => $litter,
            'owner' => Kennel::owner(),
            'terms' => Setting::get('stud_terms'),
            'back' => route('admin.litters.edit', $litter).'#akc',
            'backLabel' => 'Back to the litter',
        ]);
    }
}
