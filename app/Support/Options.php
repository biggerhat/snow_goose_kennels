<?php

namespace App\Support;

use App\Enums\CoatColor;
use App\Enums\ContactType;
use App\Enums\DogLeftReason;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Enums\LitterStatus;
use App\Enums\PuppyStatus;
use App\Enums\WaitlistStatus;

class Options
{
    /** @return array<string, list<array{value: string, label: string}>> */
    public static function all(): array
    {
        return [
            'sex' => DogSex::options(),
            'role' => DogRole::options(),
            'leftReason' => DogLeftReason::options(),
            'color' => CoatColor::options(),
            'litterStatus' => LitterStatus::options(),
            'puppyStatus' => PuppyStatus::options(),
            'contactType' => ContactType::options(),
            'waitlistStatus' => WaitlistStatus::options(),
        ];
    }
}
