<?php

namespace App\Enums;

enum HealthRecordType: string
{
    use HasOptions;

    case Vaccine = 'vaccine';
    case Deworming = 'deworming';
    case Parasite = 'parasite';
    case VetVisit = 'vet_visit';
    case Medication = 'medication';
    case Surgery = 'surgery';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Vaccine => 'Vaccine',
            self::Deworming => 'Deworming',
            self::Parasite => 'Flea, tick or heartworm',
            self::VetVisit => 'Vet visit or checkup',
            self::Medication => 'Medication',
            self::Surgery => 'Surgery or procedure',
            self::Other => 'Other',
        };
    }
}
