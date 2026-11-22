<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    use HasOptions;

    case Vet = 'vet';
    case Food = 'food';
    case HealthTesting = 'health_testing';
    case AkcFees = 'akc_fees';
    case Supplies = 'supplies';
    case Advertising = 'advertising';
    case Travel = 'travel';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Vet => 'Vet and medicine',
            self::Food => 'Food',
            self::HealthTesting => 'Health testing (OFA, DNA…)',
            self::AkcFees => 'AKC fees and registration',
            self::Supplies => 'Supplies (whelping box, bedding…)',
            self::Advertising => 'Advertising and website',
            self::Travel => 'Travel and shipping',
            self::Other => 'Other',
        };
    }
}
