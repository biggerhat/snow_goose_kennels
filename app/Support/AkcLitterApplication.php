<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;

/**
 * Everything AKC's Litter Registration Application asks for, taken from what is already on file.
 *
 * `fields` uses the names of the fields in AKC's fillable PDF (revision ALITTA 03/26). If AKC changes
 * the form, unknown names are simply skipped when filling it, and the worksheet still shows every answer.
 */
class AkcLitterApplication
{
    public const FORM_URL = 'https://images.akc.org/pdf/literap.pdf';

    public const ONLINE_URL = 'https://www.akc.org/register/litter';

    /** @return array{fields: array<string, string>, sections: list<array{title: string, rows: list<array{0: string, 1: string}>}>, missing: list<array{text: string, href: string|null}>} */
    public static function build(Litter $litter): array
    {
        $litter->loadMissing(['sire.ownerContact', 'dam.ownerContact', 'puppies']);
        $sire = $litter->sire;
        $dam = $litter->dam;
        $f = [];
        $missing = [];
        $need = function (string $text, ?string $href = null) use (&$missing) {
            $missing[] = ['text' => $text, 'href' => $href];
        };
        $editLitter = route('admin.litters.edit', $litter);

        // Litter
        $born = $litter->born_on;
        if ($born) {
            $f['Month'] = $born->format('m');
            $f['Day'] = $born->format('d');
            $f['Year'] = $born->format('Y');
        } else {
            $need('The date the puppies were born', $editLitter);
        }
        $f['breed_description'] = 'Labrador Retriever';
        $males = $litter->puppies->where('sex.value', 'male')->count();
        $females = $litter->puppies->where('sex.value', 'female')->count();
        if ($litter->puppies->isEmpty()) {
            $need('The puppies (AKC asks how many males and females)', $editLitter);
        } else {
            $f['Text4'] = (string) $males;
            $f['Text5'] = (string) $females;
            $f['Text7'] = (string) ($males + $females);
        }

        // Sire
        $sireOwner = $sire ? self::ownerOf($sire) : null;
        if (! $sire) {
            $need('The sire (father)', $editLitter);
        } else {
            $sire->registry_number ? $f['sire_number'] = $sire->registry_number : $need("{$sire->name}’s registration number", route('admin.dogs.edit', $sire));
            $f['text_sire_name_full'] = $sire->registered_name ?: $sire->name;
            ! $sire->registered_name && $need("{$sire->name}’s registered name (the everyday name is used for now)", route('admin.dogs.edit', $sire));
            self::person($f, $sireOwner, 'name1', 'name2', ['phone1', 'phone2', 'phone3'], 'email1');
            if (! $sireOwner['first'] && ! $sireOwner['last']) {
                $need($sire->is_ours ? 'Your name, in Kennel owner on the Website text page' : "Who owns {$sire->name}", $sire->is_ours ? route('admin.site.edit') : route('admin.dogs.edit', $sire));
            }
        }

        // Dam
        if (! $dam) {
            $need('The dam (mother)', $editLitter);
        } else {
            $owner = self::ownerOf($dam);
            $dam->registry_number ? $f['dam_number'] = $dam->registry_number : $need("{$dam->name}’s registration number", route('admin.dogs.edit', $dam));
            $f['text_dam_name_full'] = $dam->registered_name ?: $dam->name;
            ! $dam->registered_name && $need("{$dam->name}’s registered name (the everyday name is used for now)", route('admin.dogs.edit', $dam));
            self::person($f, $owner, 'name3', 'name4', ['phone4', 'phone5', 'phone6'], 'email2');

            $f['address1line1'] = $owner['address'];
            $f['city1'] = $owner['city'];
            strlen($owner['state']) === 2 && $f['cde_state_province'] = strtoupper($owner['state']);
            $zip = preg_replace('/\D/', '', $owner['zip']);
            $zip !== '' && $f['cde_postal_code'] = substr($zip, 0, 5);
            strlen($zip) > 5 && $f['postalcode1'] = substr($zip, 5, 4);

            if (! $owner['address'] || ! $owner['city'] || ! $owner['zip']) {
                $need($dam->is_ours ? 'Your mailing address, in Kennel owner on the Website text page' : "{$dam->name}’s owner’s mailing address", $dam->is_ours ? route('admin.site.edit') : ($dam->ownerContact ? route('admin.contacts.edit', $dam->ownerContact) : route('admin.dogs.edit', $dam)));
            }

            if ($dam->co_owner_name) {
                [$first, $last] = self::splitName($dam->co_owner_name);
                $f['name5'] = $first;
                $f['name6'] = $last;
            }
        }

        $f = array_filter($f, fn ($v) => $v !== '' && $v !== null);

        return [
            'fields' => $f,
            'sections' => self::sections($litter, $sire, $dam, $sireOwner, $dam ? self::ownerOf($dam) : null, $males, $females),
            'missing' => $missing,
        ];
    }

    /** @return array{first: string, last: string, address: string, city: string, state: string, zip: string, phone: string, email: string} */
    private static function ownerOf(Dog $dog): array
    {
        if ($dog->is_ours) {
            return Kennel::owner();
        }

        $c = $dog->ownerContact;

        return $c ? self::contactAsOwner($c) : ['first' => '', 'last' => '', 'address' => '', 'city' => '', 'state' => '', 'zip' => '', 'phone' => '', 'email' => ''];
    }

    /** @return array{first: string, last: string, address: string, city: string, state: string, zip: string, phone: string, email: string} */
    private static function contactAsOwner(Contact $c): array
    {
        [$first, $last] = self::splitName($c->name);

        return ['first' => $first, 'last' => $last, 'address' => (string) $c->address, 'city' => (string) $c->city, 'state' => (string) $c->state, 'zip' => (string) $c->postal_code, 'phone' => (string) $c->phone, 'email' => (string) $c->email];
    }

    /** @return array{0: string, 1: string} */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return count($parts) <= 1 ? [(string) ($parts[0] ?? ''), ''] : [implode(' ', array_slice($parts, 0, -1)), (string) end($parts)];
    }

    /** @return array{0: string, 1: string, 2: string} */
    public static function splitPhone(string $phone): array
    {
        $d = preg_replace('/\D/', '', $phone) ?? '';
        $d = strlen($d) === 11 && str_starts_with($d, '1') ? substr($d, 1) : $d;

        return strlen($d) === 10 ? [substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 4)] : ['', '', ''];
    }

    /**
     * @param  array<string, string>  $f
     * @param  array<string, string>  $owner
     * @param  list<string>  $phoneFields
     */
    private static function person(array &$f, array $owner, string $first, string $last, array $phoneFields, string $email): void
    {
        $f[$first] = $owner['first'];
        $f[$last] = $owner['last'];
        [$a, $b, $c] = self::splitPhone($owner['phone']);
        $f[$phoneFields[0]] = $a;
        $f[$phoneFields[1]] = $b;
        $f[$phoneFields[2]] = $c;
        $f[$email] = $owner['email'];
    }

    /**
     * @param  array<string, string>|null  $sireOwner
     * @param  array<string, string>|null  $damOwner
     * @return list<array{title: string, rows: list<array{0: string, 1: string}>}>
     */
    private static function sections(Litter $litter, ?Dog $sire, ?Dog $dam, ?array $sireOwner, ?array $damOwner, int $males, int $females): array
    {
        $v = fn ($x) => filled($x) ? (string) $x : '';
        $name = fn (?array $o) => $o ? trim(($o['first'] ?? '').' '.($o['last'] ?? '')) : '';
        $address = fn (?array $o) => $o ? collect([$o['address'] ?? '', $o['city'] ?? '', trim(($o['state'] ?? '').' '.($o['zip'] ?? ''))])->filter()->join(', ') : '';

        return [
            ['title' => 'The litter', 'rows' => [
                ['Litter date of birth', $litter->born_on?->format('m / d / Y') ?? ''],
                ['Breed', 'Labrador Retriever'],
                ['Number to be registered', $litter->puppies->isEmpty() ? '' : "{$males} males + {$females} females = ".($males + $females).' puppies'],
            ]],
            ['title' => 'Sire (male), signed by the sire’s owner on the date of mating', 'rows' => [
                ['AKC number of sire', $v($sire?->registry_number)],
                ['Registered name of sire', $v($sire?->registered_name ?: $sire?->name)],
                ['Owner', $name($sireOwner)],
                ['Phone', $v($sireOwner['phone'] ?? '')],
                ['Email', $v($sireOwner['email'] ?? '')],
            ]],
            ['title' => 'Dam (female), signed by all owners or lessees of the dam on the date of birth', 'rows' => [
                ['AKC number of dam', $v($dam?->registry_number)],
                ['Registered name of dam', $v($dam?->registered_name ?: $dam?->name)],
                ['1st owner', $name($damOwner)],
                ['Mailing address (AKC sends the litter kit here)', $address($damOwner)],
                ['Phone', $v($damOwner['phone'] ?? '')],
                ['Email', $v($damOwner['email'] ?? '')],
                ['2nd owner', $v($dam?->co_owner_name)],
            ]],
        ];
    }
}
