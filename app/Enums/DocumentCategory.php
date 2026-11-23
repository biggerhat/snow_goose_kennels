<?php

namespace App\Enums;

enum DocumentCategory: string
{
    use HasOptions;

    case Registration = 'registration';
    case Pedigree = 'pedigree';
    case HealthCertificate = 'health_certificate';
    case Dna = 'dna';
    case Vet = 'vet';
    case StudAgreement = 'stud_agreement';
    case Sale = 'sale';
    case LitterApplication = 'litter_application';
    case EmergencyPlan = 'emergency_plan';
    case Inspection = 'inspection';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration papers',
            self::Pedigree => 'Pedigree',
            self::HealthCertificate => 'Health test certificate (OFA, CAER…)',
            self::Dna => 'DNA report',
            self::Vet => 'Vet record',
            self::StudAgreement => 'Stud agreement',
            self::Sale => 'Bill of sale / sales agreement',
            self::LitterApplication => 'AKC litter application',
            self::EmergencyPlan => 'Emergency plan',
            self::Inspection => 'Inspection report or kennel license',
            self::Other => 'Other',
        };
    }
}
