<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Adds lineage, outside dogs, AKC details, health records and papers to the demo kennel. Dev only. */
class DemoRecordsSeeder extends Seeder
{
    public function run(): void
    {
        $dogs = Dog::all()->keyBy('name');

        if ($dogs->isEmpty() || Dog::where('is_ours', false)->exists()) {
            return;
        }

        $ridge = Contact::firstWhere('name', 'Ridgeline Retrievers') ?? Contact::create(['name' => 'Ridgeline Retrievers', 'type' => 'stud_owner', 'city' => 'Bend', 'state' => 'OR', 'phone' => '(555) 010-0999']);
        $cedar = Contact::create(['name' => 'Cedar Creek Kennel', 'type' => 'breeder', 'address' => '88 Mill Rd', 'city' => 'Eugene', 'state' => 'OR', 'postal_code' => '97401', 'phone' => '(555) 010-0777']);

        $outside = fn (string $name, string $sex, ?string $reg, string $registered, $owner, array $extra = []) => Dog::create($extra + [
            'name' => $name, 'registered_name' => $registered, 'registry_number' => $reg, 'sex' => $sex, 'role' => 'other',
            'is_ours' => false, 'is_public' => false, 'owner_contact_id' => $owner->id, 'color' => 'black', 'birth_date' => now()->subYears(7),
        ]);

        $maverickSire = $outside('Ridgeline Titan', 'male', 'SR70012341', 'Ridgeline Titan of Bend', $ridge, ['titles' => 'MH', 'birth_date' => now()->subYears(11)]);
        $maverickDam = $outside('Ridgeline Sage', 'female', 'SR70012342', 'Ridgeline Sage', $ridge, ['birth_date' => now()->subYears(10)]);
        $maverick = $outside('Ridgeline Maverick', 'male', 'SR80023451', 'Ridgeline Maverick of the High Desert', $ridge, ['titles' => 'MH', 'dna_profile' => 'V789012', 'birth_date' => now()->subYears(5), 'sire_id' => $maverickSire->id, 'dam_id' => $maverickDam->id]);
        $maggie = $outside('Cedar Creek Maggie', 'female', 'SR80023452', 'Cedar Creek Maggie May', $cedar, ['color' => 'yellow', 'birth_date' => now()->subYears(6)]);
        $dash = $outside('Cedar Creek Dash', 'male', 'SR80023453', 'Cedar Creek Dash', $cedar, ['color' => 'yellow', 'birth_date' => now()->subYears(6)]);

        $ours = [
            'Bella' => ['sire' => $maverick, 'dam' => $maggie, 'reg' => 'SR90034561', 'chip' => '985112000000101', 'titles' => 'SH'],
            'Honey' => ['sire' => $dash, 'dam' => $maggie, 'reg' => 'SR90034562', 'chip' => '985112000000102', 'titles' => null],
            'Maple' => ['sire' => $maverick, 'dam' => $maggie, 'reg' => 'SR90034563', 'chip' => '985112000000103', 'titles' => null],
            'Willow' => ['sire' => $dash, 'dam' => $maggie, 'reg' => 'SR90034564', 'chip' => '985112000000104', 'titles' => null],
            'Duke' => ['sire' => $maverick, 'dam' => $maggie, 'reg' => 'SR90034565', 'chip' => '985112000000105', 'titles' => 'MH', 'dna' => 'V123456'],
            'Ranger' => ['sire' => $maverickSire, 'dam' => $maverickDam, 'reg' => 'SR90034566', 'chip' => '985112000000106', 'titles' => null, 'dna' => 'V123457'],
            'Tucker' => ['sire' => $dash, 'dam' => null, 'reg' => 'SR90034567', 'chip' => '985112000000107', 'titles' => 'SH', 'dna' => 'V123458'],
            'Daisy' => ['sire' => null, 'dam' => null, 'reg' => 'SR90034568', 'chip' => '985112000000108', 'titles' => null],
        ];

        foreach ($ours as $name => $d) {
            $dog = $dogs[$name];
            $dog->update([
                'sire_id' => $d['sire']?->id, 'dam_id' => $d['dam']?->id, 'registry_number' => $d['reg'], 'microchip' => $d['chip'],
                'titles' => $d['titles'], 'dna_profile' => $d['dna'] ?? null,
                'registered_name' => $dog->registered_name ?? "Snow Goose's {$name}",
                'acquired_on' => $dog->birth_date,
            ]);
        }

        // Daisy has retired to the sofa; she is still ours, but note when she stopped breeding.
        $dogs['Daisy']->update(['private_notes' => 'Retired from breeding. Spoiled rotten.']);

        $this->litterDetails($dogs, $maverick);
        $this->buyers();
        $this->healthRecords($dogs);
        $this->documents($dogs);
    }

    private function litterDetails($dogs, Dog $maverick): void
    {
        foreach (Litter::with('puppies')->get() as $litter) {
            $litter->update([
                'breeding_method' => 'natural',
                'bred_place' => 'Boise, ID',
                'bred_handler' => 'Owner',
            ]);
        }

        // The planned spring litter uses an outside sire with a stud fee.
        $planned = Litter::where('status', 'planned')->first();
        $planned?->update(['sire_id' => $maverick->id, 'bred_place' => 'Ridgeline Retrievers, Bend, OR', 'bred_handler' => 'Ridgeline Retrievers', 'stud_fee' => 1200]);

        foreach (Litter::whereIn('status', ['all_home'])->get() as $i => $litter) {
            $litter->update(['akc_applied_on' => $litter->born_on->copy()->addWeeks(5), 'akc_litter_number' => 'NP'.(60000 + $i * 111)]);

            foreach ($litter->puppies as $n => $pup) {
                $went = $litter->go_home_on;
                $pup->update([
                    'microchip' => (string) (985000000000000 + $litter->id * 100 + $n),
                    'registration_type' => $n % 3 === 0 ? 'limited' : 'full',
                    'akc_number' => 'NP'.(70000 + $litter->id * 20 + $n).'/01',
                    'papers_given_on' => $went, 'went_home_on' => $went, 'vet_checked_on' => $went->copy()->subDays(3),
                ]);
            }
        }

        // The yellow litter: older than 5 months would need an AKC reminder; this one is 4 weeks old, so just born.
        Litter::where('status', 'on_ground')->orderBy('born_on')->first()?->update(['stillborn_count' => 1]);
    }

    private function buyers(): void
    {
        foreach (Contact::where('type', 'buyer')->get() as $i => $c) {
            $c->update([
                'address' => $c->address ?: (100 + $i * 7).' '.['Oak', 'Maple', 'Pine', 'Cedar', 'Willow'][$i % 5].' St',
                'postal_code' => $c->postal_code ?: '83'.sprintf('%03d', 700 + $i),
            ]);
        }
    }

    private function healthRecords($dogs): void
    {
        foreach (['Bella', 'Honey', 'Maple', 'Willow', 'Duke', 'Ranger', 'Tucker'] as $i => $name) {
            $dog = $dogs[$name];
            $dog->healthRecords()->createMany([
                ['type' => 'vaccine', 'name' => 'Rabies', 'given_on' => now()->subMonths(10)->subDays($i), 'next_due_on' => now()->addMonths(2)->addDays($i * 20 - 70), 'given_by' => 'Dr. Allen', 'lot_number' => 'R'.(4000 + $i)],
                ['type' => 'vaccine', 'name' => 'DHPP', 'given_on' => now()->subMonths(8), 'next_due_on' => now()->addMonths(16), 'given_by' => 'Dr. Allen'],
                ['type' => 'parasite', 'name' => 'Heartworm preventive', 'given_on' => now()->subDays(20), 'next_due_on' => now()->addDays(10 + $i), 'given_by' => 'Owner'],
                ['type' => 'vet_visit', 'name' => 'Annual exam', 'given_on' => now()->subMonths(5), 'notes' => 'All good. Weight 62 lb.', 'given_by' => 'Dr. Allen'],
            ]);
        }

        $yellow = Litter::where('status', 'on_ground')->orderBy('born_on')->first();

        foreach ($yellow?->puppies ?? [] as $pup) {
            $pup->healthRecords()->createMany([
                ['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => $yellow->born_on->copy()->addWeeks(2), 'next_due_on' => $yellow->born_on->copy()->addWeeks(4)->addDays(1), 'given_by' => 'Owner'],
                ['type' => 'deworming', 'name' => 'Pyrantel', 'given_on' => $yellow->born_on->copy()->addWeeks(4)->addDays(-2), 'next_due_on' => now()->addDays(2), 'given_by' => 'Owner'],
            ]);
        }
    }

    private function documents($dogs): void
    {
        $make = function ($owner, string $title, string $category, string $text, array $extra = []) {
            $path = 'documents/'.now()->format('Y/m').'/demo-'.md5($title.$owner->getKey().$owner::class).'.pdf';
            Storage::disk('local')->put($path, $this->pdf($title, $text));
            $owner->documents()->create($extra + [
                'category' => $category, 'title' => $title, 'path' => $path, 'original_name' => str($title)->slug().'.pdf',
                'mime' => 'application/pdf', 'size' => strlen(Storage::disk('local')->get($path)),
            ]);
        };

        $make($dogs['Bella'], 'AKC registration certificate', 'registration', 'Demo registration certificate for Bella (not a real document).', ['issued_on' => now()->subYears(4)]);
        $make($dogs['Bella'], 'Pedigree (4 generations)', 'pedigree', 'Demo pedigree for Bella (not a real document).', ['issued_on' => now()->subYears(2)]);
        $make($dogs['Tucker'], 'Rabies certificate', 'vet', 'Demo rabies certificate for Tucker (not a real document).', ['issued_on' => now()->subMonths(11), 'expires_on' => now()->addDays(18)]);
        $make($dogs['Duke'], 'OFA hips and elbows results', 'health_certificate', 'Demo OFA results for Duke (not a real document).', ['issued_on' => now()->subYears(3)]);

        $planned = Litter::where('status', 'planned')->first();
        $planned && $make($planned, 'Stud agreement with Ridgeline Retrievers', 'stud_agreement', 'Demo stud agreement (not a real document).', ['issued_on' => now()->subDays(12)]);
    }

    /** A tiny valid one-page PDF, so the demo has real files to open. */
    private function pdf(string $title, string $text): string
    {
        $esc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $stream = "BT /F1 20 Tf 60 740 Td ({$esc($title)}) Tj ET\nBT /F1 12 Tf 60 700 Td ({$esc($text)}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $o) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$o}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
