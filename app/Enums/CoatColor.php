<?php

namespace App\Enums;

enum CoatColor: string
{
    use HasOptions;

    case Black = 'black';
    case Chocolate = 'chocolate';
    case Yellow = 'yellow';
    case FoxRed = 'fox_red';

    public function label(): string
    {
        return match ($this) {
            self::Black => 'Black',
            self::Chocolate => 'Chocolate',
            self::Yellow => 'Yellow',
            self::FoxRed => 'Fox red',
        };
    }
}
