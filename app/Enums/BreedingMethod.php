<?php

namespace App\Enums;

enum BreedingMethod: string
{
    use HasOptions;

    case Natural = 'natural';
    case AiFresh = 'ai_fresh';
    case AiFrozen = 'ai_frozen';

    public function label(): string
    {
        return match ($this) {
            self::Natural => 'Natural mating',
            self::AiFresh => 'Artificial insemination (fresh semen)',
            self::AiFrozen => 'Artificial insemination (frozen semen)',
        };
    }
}
