<?php

namespace App\Enums;

enum LitterStatus: string
{
    use HasOptions;

    case Planned = 'planned';
    case Expected = 'expected';
    case OnGround = 'on_ground';
    case AllHome = 'all_home';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::Expected => 'Expected',
            self::OnGround => 'On the ground',
            self::AllHome => 'All gone home',
        };
    }
}
