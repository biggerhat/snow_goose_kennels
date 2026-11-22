<?php

namespace App\Enums;

enum AchievementKind: string
{
    use HasOptions;

    case HuntTest = 'hunt_test';
    case FieldTrial = 'field_trial';
    case Show = 'show';
    case Performance = 'performance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HuntTest => 'Hunt test',
            self::FieldTrial => 'Field trial',
            self::Show => 'Conformation show',
            self::Performance => 'Obedience, agility or other sport',
            self::Other => 'Other',
        };
    }
}
