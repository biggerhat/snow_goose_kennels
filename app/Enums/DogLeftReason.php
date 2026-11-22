<?php

namespace App\Enums;

enum DogLeftReason: string
{
    use HasOptions;

    case Sold = 'sold';
    case GivenAway = 'given_away';
    case Died = 'died';

    public function label(): string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::GivenAway => 'Given away',
            self::Died => 'Passed away',
        };
    }
}
