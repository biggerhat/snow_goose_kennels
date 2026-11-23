<?php

namespace App\Support;

use App\Enums\BreedingMethod;
use App\Enums\PuppyStatus;
use App\Models\Dog;
use App\Models\Litter;

/**
 * A plain-language checklist of what AKC expects for a litter and for a dog's record.
 *
 * These checks help her remember; they are not the rules themselves. AKC's rulebook is the
 * authority and changes over time (rules reflected here: dam 8 months to 12 years and sire 7 months
 * to 12 years at mating, litter application within 6 months of birth, DNA profile for frequently
 * used sires, imports, conditional "Q" registrations and artificial insemination, and 5 years of records).
 */
class AkcChecks
{
    /** @return array{items: list<array<string, mixed>>, todo: int, warn: int} */
    public static function forLitter(Litter $litter): array
    {
        $litter->loadMissing(['sire', 'dam', 'puppies.buyer']);
        $items = [];
        $add = function (string $key, string $status, string $title, ?string $detail = null) use (&$items) {
            $items[] = compact('key', 'status', 'title', 'detail');
        };
        $sire = $litter->sire;
        $dam = $litter->dam;

        // Parents and registration numbers
        if (! $sire || ! $dam) {
            $add('parents', 'todo', 'Choose the sire and the dam', 'AKC registers a litter from two registered parents.');
        } else {
            $missing = collect([$sire, $dam])->filter(fn (Dog $d) => blank($d->registry_number))->pluck('name');
            $missing->isEmpty()
                ? $add('parents', 'ok', 'Both parents have registration numbers')
                : $add('parents', 'todo', 'Add the registration number for '.$missing->join(' and '), 'Both parents must be AKC registered. Open the dog and fill in the registration number.');
        }

        // Ages at mating
        $bred = $litter->bred_on;
        if ($bred && $dam) {
            self::age($add, 'dam_age', $dam, $bred, 8, 'month', 'dam', 12);
        } elseif ($dam) {
            $add('dam_age', 'todo', 'Add the date bred', 'It is needed to check the dam is old enough (8 months to 12 years at mating).');
        }
        if ($bred && $sire) {
            self::age($add, 'sire_age', $sire, $bred, 7, 'month', 'sire', 12);
        }

        // Ownership change or lease between mating and birth
        if ($dam && $bred && $litter->born_on && $dam->left_on && $dam->left_on->between($bred, $litter->born_on)) {
            $add('ownership', 'warn', "{$dam->name} left the kennel between mating and birth", 'AKC will not register a litter if the dam’s ownership changed, or she was leased, between mating and birth.');
        }

        // DNA
        if ($sire) {
            $reasons = [];
            $year = ($litter->born_on ?? $litter->bred_on ?? $litter->due_on)?->year;
            $thisYear = $year ? Litter::where('sire_id', $sire->id)->get()->filter(fn (Litter $l) => ($l->born_on ?? $l->bred_on ?? $l->due_on)?->year === $year)->count() : 0;
            $lifetime = Litter::where('sire_id', $sire->id)->count();

            if ($thisYear >= 4) {
                $reasons[] = "{$sire->name} has {$thisYear} litters in {$year}";
            }
            if ($lifetime >= 7) {
                $reasons[] = "{$sire->name} has {$lifetime} litters in total";
            }
            if ($litter->breeding_method && $litter->breeding_method !== BreedingMethod::Natural) {
                $reasons[] = 'artificial insemination was used';
            }
            foreach ([$sire, $dam] as $parent) {
                if ($parent && str_starts_with(strtoupper((string) $parent->registry_number), 'Q')) {
                    $reasons[] = "{$parent->name} has a conditional (Q) registration";
                }
            }

            if ($reasons) {
                filled($sire->dna_profile)
                    ? $add('dna', 'ok', 'DNA profile on file for the sire', implode('; ', $reasons).'. AKC requires it.')
                    : $add('dna', 'warn', 'AKC needs a DNA profile for the sire', implode('; ', $reasons).'. Get the sire’s DNA profile and enter its number on his page.');
            }
        }

        // Multiple sires, artificial insemination, co-owners
        if ($litter->multiple_sires) {
            $add('multi', 'warn', 'More than one possible sire', 'AKC needs DNA profiles for every possible sire, the dam and all the puppies before it will register a litter like this.');
        }
        if ($litter->breeding_method === BreedingMethod::AiFresh) {
            $add('ai', 'todo', 'Litters from fresh semen use a separate AKC application', 'AKC has its own form for artificial insemination with fresh extended semen, and needs the sire’s DNA profile.');
        } elseif ($litter->breeding_method === BreedingMethod::AiFrozen) {
            $add('ai', 'todo', 'Frozen semen must be reported to AKC', 'The collection is reported to AKC with DNA certification before the litter is registered. Check AKC’s current frozen semen form.');
        }
        if ($dam?->co_owner_name) {
            $add('coowner', 'info', "{$dam->name} has a co-owner ({$dam->co_owner_name})", 'Every owner or lessee of the dam has to sign the litter application.');
        }

        // Signatures
        if ($litter->born_on && $sire && $dam) {
            $who = array_filter([
                $litter->sire_owner_signed_on ? null : ($sire->is_ours ? 'the sire’s owner (you)' : 'the sire’s owner'.($sire->ownerContact ? " ({$sire->ownerContact->name})" : '')),
                $litter->dam_owner_signed_on ? null : 'the dam’s owner'.($dam->co_owner_name ? "s ({$dam->co_owner_name} too)" : ''),
            ]);
            $who
                ? $add('signed', 'todo', 'The application still needs a signature', 'Waiting for '.implode(' and ', $who).'. Enter the dates in Breeding & AKC when they have signed.')
                : $add('signed', 'ok', 'Signed by the sire’s and the dam’s owners');
        }

        // Litter application
        if ($litter->born_on) {
            $deadline = $litter->born_on->copy()->addMonths(6);

            if ($litter->akc_applied_on) {
                $add('applied', 'ok', 'AKC litter application sent on '.$litter->akc_applied_on->format('M j, Y'));
                blank($litter->akc_litter_number)
                    ? $add('number', 'todo', 'Enter the AKC litter number', 'It is on the paperwork AKC sends back with the litter kit.')
                    : $add('number', 'ok', 'AKC litter number recorded');
            } else {
                $days = (int) today()->diffInDays($deadline, false);
                $when = $deadline->format('M j, Y');
                $days < 0
                    ? $add('applied', 'warn', 'The 6-month deadline for the litter application has passed', "It was {$when}. Call AKC to ask what is needed to register this litter.")
                    : $add('applied', $days <= 30 ? 'warn' : 'todo', "Send the AKC litter application by {$when}", $days.' days left. AKC wants it within 6 months of birth.');
            }
        } elseif ($litter->status->value === 'on_ground' || $litter->status->value === 'all_home') {
            $add('born', 'todo', 'Add the birth date', 'It is needed for the litter application.');
        }

        // Puppies
        $pups = $litter->puppies;
        if ($litter->born_on) {
            if ($pups->isEmpty()) {
                $add('puppies', 'todo', 'Add the puppies', 'AKC asks for the number of males and females in the litter, and their colors.');
            } else {
                $m = $pups->where('sex.value', 'male')->count();
                $add('puppies', 'ok', "{$pups->count()} puppies recorded ({$m} males, ".($pups->count() - $m).' females)');
            }
        }

        $gone = $pups->filter(fn ($p) => in_array($p->status, [PuppyStatus::Reserved, PuppyStatus::Home], true));
        if ($gone->isNotEmpty()) {
            $noBuyer = $gone->filter(fn ($p) => ! $p->buyer)->count();
            $noAddress = $gone->filter(fn ($p) => $p->buyer && (blank($p->buyer->address) || blank($p->buyer->city) || blank($p->buyer->state)))->count();
            if ($noBuyer || $noAddress) {
                $bits = array_filter([$noBuyer ? "{$noBuyer} without a buyer" : null, $noAddress ? "{$noAddress} where the buyer has no full address" : null]);
                $add('buyers', 'todo', 'Buyer name and address for each sold puppy', implode(', ', $bits).'. AKC wants each buyer’s name and address in your litter records.');
            } else {
                $add('buyers', 'ok', 'Every reserved or sold puppy has a buyer with an address');
            }
        }

        $home = $pups->where('status', PuppyStatus::Home);
        if ($home->isNotEmpty()) {
            $noPapers = $home->filter(fn ($p) => blank($p->registration_type) || ($p->registration_type?->value !== 'none' && ! $p->papers_given_on))->count();
            $noPapers
                ? $add('papers', 'todo', 'Record the papers given for each puppy that went home', "{$noPapers} still to do: full or limited registration (or no papers) and the date the papers were handed over. With no papers, a bill of sale is needed.")
                : $add('papers', 'ok', 'Papers recorded for every puppy that went home');
        }

        $add('keep', 'info', 'Keep these records for at least 5 years', 'AKC asks breeders to keep litter and ownership records for 5 years after a dog dies, is sold or is given away. The Records page prints them.');

        return [
            'items' => $items,
            'todo' => count(array_filter($items, fn ($i) => $i['status'] === 'todo')),
            'warn' => count(array_filter($items, fn ($i) => $i['status'] === 'warn')),
        ];
    }

    /** @return array{items: list<array<string, mixed>>, todo: int, warn: int} */
    public static function forDog(Dog $dog): array
    {
        $items = [];
        $add = function (string $key, string $status, string $title, ?string $detail = null) use (&$items) {
            $items[] = compact('key', 'status', 'title', 'detail');
        };

        $dog->birth_date ? $add('born', 'ok', 'Birth date recorded') : $add('born', 'todo', 'Add the birth date', 'AKC records and litter checks both use it.');
        filled($dog->registry_number) ? $add('reg', 'ok', 'Registration number recorded') : $add('reg', 'todo', 'Add the registration number', 'Needed for the dog to be a parent of an AKC litter.');
        filled($dog->microchip) ? $add('chip', 'ok', 'Microchip recorded') : $add('chip', 'todo', 'Add the microchip number, if the dog has one');

        if ($dog->is_ours) {
            ($dog->birth_date || $dog->acquired_on) ? $add('own', 'ok', 'Ownership dates recorded') : $add('own', 'todo', 'Add the date the dog came to the kennel', 'AKC’s ownership record starts with when you got the dog.');
            if ($dog->left_on && ! $dog->left_reason) {
                $add('left', 'todo', 'Say why the dog left the kennel');
            }
        } else {
            $dog->owner_contact_id ? $add('owner', 'ok', 'Owner recorded') : $add('owner', 'todo', 'Choose the owner', 'AKC asks who owns the other dog in a mating.');
        }

        if (str_starts_with(strtoupper((string) $dog->registry_number), 'Q') && blank($dog->dna_profile)) {
            $add('dna', 'warn', 'Conditional (Q) registration needs a DNA profile', 'AKC needs both parents’ DNA profiles before it will register a litter.');
        }

        return [
            'items' => $items,
            'todo' => count(array_filter($items, fn ($i) => $i['status'] === 'todo')),
            'warn' => count(array_filter($items, fn ($i) => $i['status'] === 'warn')),
        ];
    }

    private static function age(callable $add, string $key, Dog $dog, \DateTimeInterface $bred, int $minMonths, string $unit, string $role, int $maxYears): void
    {
        if (! $dog->birth_date) {
            $add($key, 'todo', "Add {$dog->name}’s birth date", "It is needed to check the {$role} was old enough when bred.");

            return;
        }

        $months = (int) $dog->birth_date->diffInMonths($bred);
        $age = $months >= 24 ? intdiv($months, 12).' years' : $months.' months';

        if ($months < $minMonths) {
            $add($key, 'warn', "{$dog->name} was only {$age} old when bred", "AKC will not register a litter from a {$role} under {$minMonths} months old at mating without supporting proof.");
        } elseif ($months >= $maxYears * 12) {
            $add($key, 'warn', "{$dog->name} was {$age} old when bred", "AKC will not register a litter from a {$role} over {$maxYears} years old at mating without supporting proof.");
        } else {
            $add($key, 'ok', "{$dog->name} was {$age} old when bred", 'Within the AKC age range.');
        }
    }
}
