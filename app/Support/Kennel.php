<?php

namespace App\Support;

use App\Models\Setting;

/** Who the kennel's owner is, for AKC forms. Private: never sent to the public site. */
class Kennel
{
    public const OWNER_KEYS = ['owner_first_name', 'owner_last_name', 'owner_address', 'owner_city', 'owner_state', 'owner_zip', 'owner_phone', 'owner_email'];

    /** @return array{first: string, last: string, address: string, city: string, state: string, zip: string, phone: string, email: string} */
    public static function owner(): array
    {
        $contact = Setting::contact();

        return [
            'first' => (string) Setting::get('owner_first_name'),
            'last' => (string) Setting::get('owner_last_name'),
            'address' => (string) Setting::get('owner_address'),
            'city' => (string) Setting::get('owner_city'),
            'state' => (string) Setting::get('owner_state'),
            'zip' => (string) Setting::get('owner_zip'),
            'phone' => (string) (Setting::get('owner_phone') ?: $contact['phone']),
            'email' => (string) (Setting::get('owner_email') ?: $contact['email']),
        ];
    }

    public static function ownerComplete(): bool
    {
        $o = self::owner();

        return $o['first'] !== '' && $o['last'] !== '' && $o['address'] !== '' && $o['city'] !== '' && $o['state'] !== '' && $o['zip'] !== '';
    }
}
