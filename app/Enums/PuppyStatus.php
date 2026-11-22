<?php

namespace App\Enums;

enum PuppyStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Reserved = 'reserved';
    case Home = 'home';
    case Kept = 'kept';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Home => 'Gone home',
            self::Kept => 'Kept',
        };
    }
}
