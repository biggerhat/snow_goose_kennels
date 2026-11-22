<?php

namespace App\Enums;

enum ContactType: string
{
    use HasOptions;

    case Buyer = 'buyer';
    case Vet = 'vet';
    case StudOwner = 'stud_owner';
    case Breeder = 'breeder';
    case Business = 'business';
    case Friend = 'friend';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Buyer',
            self::Vet => 'Vet',
            self::StudOwner => 'Stud owner',
            self::Breeder => 'Breeder',
            self::Business => 'Business',
            self::Friend => 'Friend / family',
            self::Other => 'Other',
        };
    }
}
