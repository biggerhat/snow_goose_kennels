<?php

namespace Database\Seeders;

use App\Models\Dog;
use App\Models\KennelProfile;
use App\Models\Litter;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Owner details, titles, signatures and a kennel emergency plan for the demo kennel. Dev only. */
class DemoAkcSeeder extends Seeder
{
    public function run(): void
    {
        if (Setting::get('owner_first_name')) {
            return;
        }

        foreach (['owner_first_name' => 'Mary', 'owner_last_name' => 'Demo', 'owner_address' => '12 Marsh Road', 'owner_city' => 'Boise', 'owner_state' => 'ID', 'owner_zip' => '83702', 'owner_phone' => '(208) 555-0142', 'owner_email' => 'mary@example.com'] as $k => $v) {
            Setting::put($k, $v);
        }

        $achievements = [
            'Duke' => [['MH', 'Master Hunter', 'hunt_test', now()->subYears(2), 'Treasure Valley Hunt Test'], ['SH', 'Senior Hunter', 'hunt_test', now()->subYears(3), 'Spring Hunt Test']],
            'Tucker' => [['SH', 'Senior Hunter', 'hunt_test', now()->subYears(4), 'Fall Hunt Test']],
            'Bella' => [['JH', 'Junior Hunter', 'hunt_test', now()->subYears(3), 'Marsh Hunt Test']],
        ];

        foreach ($achievements as $name => $rows) {
            $dog = Dog::firstWhere('name', $name);

            foreach ($rows ?? [] as [$title, $full, $kind, $date, $event]) {
                $dog?->achievements()->create(['title' => $title, 'full_name' => $full, 'kind' => $kind, 'earned_on' => $date, 'event' => $event]);
            }

            $dog?->update(['titles' => $dog->achievements()->reorder()->orderBy('earned_on')->pluck('title')->join(', ') ?: null]);
        }

        Dog::firstWhere('name', 'Honey')?->update(['co_owner_name' => 'Pat Demo']);

        foreach (Litter::whereIn('status', ['all_home'])->get() as $litter) {
            $litter->update(['sire_owner_signed_on' => $litter->born_on->copy()->addWeeks(4), 'dam_owner_signed_on' => $litter->born_on->copy()->addWeeks(4)]);
        }

        $path = 'documents/'.now()->format('Y/m').'/demo-emergency-plan.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 100]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
        KennelProfile::instance()->documents()->create([
            'category' => 'emergency_plan', 'title' => 'Emergency plan', 'path' => $path, 'original_name' => 'emergency-plan.pdf',
            'mime' => 'application/pdf', 'size' => 200, 'issued_on' => now()->subMonths(4),
        ]);
    }
}
