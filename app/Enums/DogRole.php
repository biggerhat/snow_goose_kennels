<?php

namespace App\Enums;

enum DogRole: string
{
    use HasOptions;

    case Stud = 'stud';
    case Dam = 'dam';
    case Retired = 'retired';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Stud => 'Stud',
            self::Dam => 'Dam',
            self::Retired => 'Retired',
            self::Other => 'Other',
        };
    }
}
