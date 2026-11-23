<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Editable "how it works" sections. Left blank until the owner fills them in:
 * the site never invents policies on her behalf.
 */
class SiteContent
{
    public const SECTIONS = [
        ['key' => 'how_it_works', 'title' => 'How it works', 'hint' => 'The steps from first call to go-home day, in your own words. For example: inquire, short application, deposit to join the list, pick order, weekly photo updates, go-home day.'],
        ['key' => 'deposit_policy', 'title' => 'Deposits', 'hint' => 'How much, what it secures, and whether it is refundable or can move to another litter.'],
        ['key' => 'refund_policy', 'title' => 'Refunds and changes', 'hint' => 'What happens if you cannot place a puppy, or a buyer changes their mind.'],
        ['key' => 'go_home_policy', 'title' => 'Go-home age', 'hint' => 'When puppies go home (most states require at least 8 weeks) and what they have had by then: vet check, vaccines, deworming, microchip.'],
        ['key' => 'visit_policy', 'title' => 'Visits and meeting the parents', 'hint' => 'Whether visitors can come, when, and what to expect.'],
        ['key' => 'health_guarantee', 'title' => 'Health guarantee', 'hint' => 'What you stand behind and for how long.'],
        ['key' => 'spay_neuter_policy', 'title' => 'Spay / neuter', 'hint' => 'Your advice or requirements, and the age you recommend.'],
        ['key' => 'takeback_policy', 'title' => 'If a dog cannot be kept', 'hint' => 'Whether you will take a dog back at any age. Buyer guides look for this.'],
    ];

    /**
     * Wording for the printed papers (go-home packet, sales agreement, receipts). Private: never shown on the website.
     * Blank until the owner writes it, so a printed paper never contains terms she did not choose.
     */
    public const PAPERS = [
        ['key' => 'payment_instructions', 'title' => 'How buyers pay', 'hint' => 'Printed on deposit receipts and sales agreements. For example: check made out to…, Venmo @name, Zelle to this phone number.'],
        ['key' => 'packet_food', 'title' => 'What the puppies eat', 'hint' => 'The food brand and how much, so buyers can keep the same food at first. Printed in the go-home packet.'],
        ['key' => 'packet_care', 'title' => 'First weeks at home', 'hint' => 'Your tips for the first days: feeding times, crate, sleep, when to see a vet. Printed in the go-home packet.'],
        ['key' => 'packet_vet', 'title' => 'Your vet', 'hint' => 'Name and phone number of the vet who saw the puppies. Printed in the go-home packet.'],
        ['key' => 'contract_extra', 'title' => 'Extra terms for the sales agreement', 'hint' => 'Anything else you want in the agreement, like a registration or breeding-rights clause. Your deposit, refund, health guarantee, spay/neuter and take-back wording from How it works is added automatically.'],
        ['key' => 'stud_terms', 'title' => 'Terms for the stud agreement', 'hint' => 'What you agree with the owner of a stud or dam: fee, return service, what happens if there are no puppies. Printed on the stud agreement.'],
    ];

    /** @return list<array{key: string, title: string, body: string}> Sections that have text. */
    public static function filled(): array
    {
        $out = [];

        foreach (self::SECTIONS as $s) {
            $body = Setting::get($s['key']);

            if ($body) {
                $out[] = ['key' => $s['key'], 'title' => $s['title'], 'body' => $body];
            }
        }

        return $out;
    }

    public static function hasAny(): bool
    {
        return self::filled() !== [];
    }
}
