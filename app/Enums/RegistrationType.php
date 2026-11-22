<?php

namespace App\Enums;

enum RegistrationType: string
{
    use HasOptions;

    case Full = 'full';
    case Limited = 'limited';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Full registration',
            self::Limited => 'Limited registration',
            self::None => 'No papers',
        };
    }
}
