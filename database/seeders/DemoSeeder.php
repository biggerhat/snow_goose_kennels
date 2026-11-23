<?php

namespace Database\Seeders;

use App\Enums\CoatColor;
use App\Enums\ContactType;
use App\Enums\DogRole;
use App\Enums\DogSex;
use App\Enums\LitterStatus;
use App\Enums\PuppyStatus;
use App\Enums\WaitlistStatus;
use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Placeholder kennel for development and design review. Not for production.
 *
 * Photos are CC0 / public-domain Labs from Wikimedia Commons, kept in
 * database/seeders/demo-photos (see SOURCES.txt there). Every name, phone
 * number, certificate number and policy below is invented.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Dog::exists()) {
            $this->command?->warn('Dogs already exist; skipping demo data. Run migrate:fresh first for a clean slate.');

            return;
        }

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        );

        $this->settings();
        $dogs = $this->dogs();
        $litters = $this->litters($dogs);
        $contacts = $this->contacts();
        $this->homes($litters, $contacts);
        $this->waitlist($litters, $contacts);
        $this->testimonials();
        $this->gallery();
        $this->call(DemoAlbumsSeeder::class);
        $this->call(DemoRecordsSeeder::class);
        $this->call(DemoAkcSeeder::class);
    }

    private function settings(): void
    {
        foreach ([
            'phone' => '(555) 010-0142',
            'email' => 'hello@example.com',
            'location' => 'Boise, Idaho',
            'facebook' => 'https://facebook.com/example',
            'how_it_works' => "1. Send us an application, or call.\n2. We chat about your home and what you want in a Lab.\n3. A deposit holds your spot on the list.\n4. Picks happen in list order when the pups are about 6 weeks old.\n5. Go-home day at 8 weeks, with a puppy packet.",
            'deposit_policy' => 'A $300 deposit holds a place on the list and comes off the price of your puppy.',
            'go_home_policy' => 'Puppies go home at 8 weeks, vet checked, with first shots, deworming and a microchip.',
        ] as $key => $value) {
            Setting::put($key, $value);
        }
    }

    /** @return array<string, Dog> */
    private function dogs(): array
    {
        $rows = [
            'bella' => ['Bella', "Snow Goose's Bella of the Marsh", DogSex::Female, DogRole::Dam, CoatColor::Black, 4, 'Sweet, steady and a natural retriever. Bella loves water more than dinner.', ['black-adult', 'black-snow']],
            'honey' => ['Honey', null, DogSex::Female, DogRole::Dam, CoatColor::Yellow, 3, 'Our golden-hearted yellow girl. Soft mouth, big smile, first one to the door.', ['yellow-a8', 'lab-p1']],
            'maple' => ['Maple', "Snow Goose's Maple Sugar", DogSex::Female, DogRole::Dam, CoatColor::Chocolate, 5, 'Calm and clever. Maple runs the kennel and the pups mind her better than they mind us.', ['choc-lab', 'choc-01']],
            'willow' => ['Willow', null, DogSex::Female, DogRole::Dam, CoatColor::Yellow, 2, 'Our youngest mom. Energetic, eager and a joy in the field.', ['yellow-a9']],
            'duke' => ['Duke', "Snow Goose's Duke of the Reeds", DogSex::Male, DogRole::Stud, CoatColor::Chocolate, 5, 'Calm, athletic and always first in the pond.', ['choc-ground', 'choc-02']],
            'ranger' => ['Ranger', null, DogSex::Male, DogRole::Stud, CoatColor::Black, 4, 'A big-hearted black boy with a lovely, easy temperament.', ['black-hunting']],
            'tucker' => ['Tucker', null, DogSex::Male, DogRole::Stud, CoatColor::Yellow, 6, 'Tucker has sired some of our best hunters and our best couch potatoes.', ['yellow-a13', 'yellow-bak']],
            'daisy' => ['Daisy', null, DogSex::Female, DogRole::Retired, CoatColor::Yellow, 10, 'Retired and spoiled. Daisy supervises everything from the sofa.', ['lab-aurora']],
        ];

        $dogs = [];
        foreach ($rows as $key => [$name, $registered, $sex, $role, $color, $years, $bio, $photos]) {
            $dog = Dog::create([
                'name' => $name, 'registered_name' => $registered, 'sex' => $sex, 'role' => $role,
                'color' => $color, 'birth_date' => now()->subYears($years)->subMonths(random_int(0, 8)),
                'bio' => $bio, 'registry_number' => 'AKC SR'.random_int(10000000, 99999999),
                'microchip' => (string) random_int(900000000000000, 999999999999999),
                'private_notes' => 'Demo note: vaccinations are up to date. Prefers the shady kennel run.',
            ]);

            foreach ($photos as $i => $file) {
                $this->photo($dog, $file, $i === 0 ? "$name at home" : null, true);
            }

            if ($role !== DogRole::Retired) {
                $dog->healthTests()->createMany($this->healthTests($years));
            }

            if ($sex === DogSex::Female && $role === DogRole::Dam) {
                $dog->heatCycles()->createMany([
                    ['started_on' => now()->subMonths(14), 'ended_on' => now()->subMonths(14)->addDays(21), 'bred_on' => now()->subMonths(14)->addDays(12), 'notes' => 'Bred twice, two days apart.'],
                    ['started_on' => now()->subMonths(7), 'ended_on' => now()->subMonths(7)->addDays(20), 'bred_on' => null, 'notes' => 'Skipped this one.'],
                ]);
            }

            $dogs[$key] = $dog;
        }

        return $dogs;
    }

    /** @return list<array<string, mixed>> */
    private function healthTests(int $years): array
    {
        $when = now()->subYears(max(1, $years - 2));

        return [
            ['name' => 'OFA Hips', 'result' => 'Good', 'tested_on' => $when, 'certificate_number' => 'LR-'.random_int(100000, 999999).'G24F-PI', 'verification_url' => 'https://ofa.org/', 'is_public' => true],
            ['name' => 'OFA Elbows', 'result' => 'Normal', 'tested_on' => $when, 'certificate_number' => 'LR-EL'.random_int(10000, 99999).'M24-PI', 'verification_url' => 'https://ofa.org/', 'is_public' => true],
            ['name' => 'EIC', 'result' => 'Clear', 'tested_on' => $when, 'is_public' => true],
            ['name' => 'CNM', 'result' => 'Clear', 'tested_on' => $when, 'is_public' => true],
            ['name' => 'PRA', 'result' => 'Clear', 'tested_on' => $when, 'is_public' => true],
            ['name' => 'Eye exam (CAER)', 'result' => 'Normal', 'tested_on' => now()->subMonths(5), 'is_public' => true],
            ['name' => 'Cardiac', 'result' => 'Normal', 'tested_on' => $when, 'is_public' => false, 'notes' => 'Private: vet comment, grade 0.'],
        ];
    }

    /**
     * @param  array<string, Dog>  $dogs
     * @return array<string, Litter>
     */
    private function litters(array $dogs): array
    {
        $make = fn (array $a) => Litter::create($a + ['is_public' => true]);

        $l = [];
        $l['old'] = $make(['dam_id' => $dogs['maple']->id, 'sire_id' => $dogs['duke']->id, 'status' => LitterStatus::AllHome, 'bred_on' => now()->subMonths(14), 'due_on' => now()->subMonths(12)->subDays(10), 'born_on' => now()->subMonths(12)->subDays(8), 'go_home_on' => now()->subMonths(10)->subDays(12), 'price' => 1400, 'deposit' => 300, 'expected_count' => 7, 'description' => 'Chocolate and black pups, all in loving homes.']);
        $l['summer'] = $make(['dam_id' => $dogs['bella']->id, 'sire_id' => $dogs['ranger']->id, 'status' => LitterStatus::AllHome, 'bred_on' => now()->subMonths(7), 'born_on' => now()->subMonths(5)->subDays(10), 'go_home_on' => now()->subMonths(3)->subDays(10), 'price' => 1500, 'deposit' => 300, 'expected_count' => 8, 'description' => 'An all-black litter out of Bella and Ranger.']);
        $l['yellow'] = $make(['dam_id' => $dogs['honey']->id, 'sire_id' => $dogs['tucker']->id, 'status' => LitterStatus::OnGround, 'bred_on' => now()->subDays(92), 'born_on' => now()->subDays(29), 'go_home_on' => now()->addDays(27), 'price' => 1600, 'deposit' => 300, 'expected_count' => 9, 'description' => 'Nine yellow pups from Honey and Tucker. Raised in the house, handled daily.']);
        $l['fresh'] = $make(['dam_id' => $dogs['maple']->id, 'sire_id' => $dogs['ranger']->id, 'status' => LitterStatus::OnGround, 'bred_on' => now()->subDays(72), 'born_on' => now()->subDays(9), 'go_home_on' => now()->addDays(47), 'price' => 1600, 'deposit' => 300, 'expected_count' => 6, 'description' => 'Black and chocolate pups, just a week old.']);
        $l['expected'] = $make(['dam_id' => $dogs['willow']->id, 'sire_id' => $dogs['duke']->id, 'status' => LitterStatus::Expected, 'bred_on' => now()->subDays(28), 'due_on' => now()->addDays(35), 'go_home_on' => now()->addDays(91), 'price' => 1600, 'deposit' => 300, 'expected_count' => 7, 'description' => 'Willow’s first litter. Chocolate and yellow pups expected.']);
        $l['planned'] = $make(['dam_id' => $dogs['bella']->id, 'sire_id' => $dogs['duke']->id, 'status' => LitterStatus::Planned, 'due_on' => now()->addMonths(5), 'price' => 1600, 'deposit' => 300, 'expected_count' => 8, 'description' => 'Planned for spring: black and chocolate.']);

        // Old litters: sold puppies, kept one.
        $this->puppies($l['old'], [
            ['Hazel', DogSex::Female, CoatColor::Chocolate, PuppyStatus::Home], ['Cocoa', DogSex::Female, CoatColor::Chocolate, PuppyStatus::Home],
            ['Moose', DogSex::Male, CoatColor::Chocolate, PuppyStatus::Home], ['Bear', DogSex::Male, CoatColor::Black, PuppyStatus::Home],
            ['Shadow', DogSex::Male, CoatColor::Black, PuppyStatus::Home], ['Fern', DogSex::Female, CoatColor::Black, PuppyStatus::Home],
            ['Birch', DogSex::Male, CoatColor::Chocolate, PuppyStatus::Kept],
        ], weeks: 0);
        $this->puppies($l['summer'], [
            ['Raven', DogSex::Female, CoatColor::Black, PuppyStatus::Home], ['Onyx', DogSex::Male, CoatColor::Black, PuppyStatus::Home],
            ['Coal', DogSex::Male, CoatColor::Black, PuppyStatus::Home], ['Juniper', DogSex::Female, CoatColor::Black, PuppyStatus::Home],
            ['Sage', DogSex::Female, CoatColor::Black, PuppyStatus::Home], ['Kodiak', DogSex::Male, CoatColor::Black, PuppyStatus::Home],
            ['Pepper', DogSex::Female, CoatColor::Black, PuppyStatus::Home], ['Remy', DogSex::Male, CoatColor::Black, PuppyStatus::Home],
        ], weeks: 0);

        // Yellow litter, four weeks old, with weigh-ins.
        $this->puppies($l['yellow'], [
            ['Sunny', DogSex::Female, CoatColor::Yellow, PuppyStatus::Reserved, 'Red'], ['Butter', DogSex::Female, CoatColor::Yellow, PuppyStatus::Reserved, 'Orange'],
            ['Goldie', DogSex::Female, CoatColor::Yellow, PuppyStatus::Available, 'Yellow'], ['Wheat', DogSex::Male, CoatColor::Yellow, PuppyStatus::Reserved, 'Green'],
            ['Biscuit', DogSex::Male, CoatColor::Yellow, PuppyStatus::Available, 'Blue'], ['Honeybee', DogSex::Female, CoatColor::Yellow, PuppyStatus::Available, 'Purple'],
            ['Maize', DogSex::Male, CoatColor::Yellow, PuppyStatus::Available, 'Pink'], ['Cheddar', DogSex::Male, CoatColor::Yellow, PuppyStatus::Reserved, 'White'],
            ['Pip', DogSex::Male, CoatColor::Yellow, PuppyStatus::Available, 'Teal'],
        ], weeks: 4);

        // Fresh litter, nine days old.
        $this->puppies($l['fresh'], [
            ['', DogSex::Female, CoatColor::Black, PuppyStatus::Available, 'Red'], ['', DogSex::Female, CoatColor::Chocolate, PuppyStatus::Available, 'Orange'],
            ['', DogSex::Male, CoatColor::Black, PuppyStatus::Available, 'Blue'], ['', DogSex::Male, CoatColor::Chocolate, PuppyStatus::Available, 'Green'],
            ['', DogSex::Male, CoatColor::Black, PuppyStatus::Available, 'Purple'], ['', DogSex::Female, CoatColor::Black, PuppyStatus::Available, 'Pink'],
        ], weeks: 1);

        $this->diary($l);

        $this->photo($l['yellow'], 'pup-1', 'Four weeks old', true);
        $this->photo($l['yellow'], 'pup-perrito', null, true);
        $this->photo($l['yellow'], 'pup-seamus', 'First time outside', true);
        $this->photo($l['fresh'], 'pup-moose', 'Nine days old', true);
        $this->photo($l['summer'], 'black-snow', 'Raven and her brothers, first snow', true);

        return $l;
    }

    /** @param list<array{0: string, 1: DogSex, 2: CoatColor, 3: PuppyStatus, 4?: string}> $rows */
    private function puppies(Litter $litter, array $rows, int $weeks): void
    {
        foreach ($rows as $i => $r) {
            $birth = random_int(330, 480);
            $puppy = $litter->puppies()->create([
                'name' => $r[0] ?: null, 'collar' => $r[4] ?? null, 'sex' => $r[1], 'color' => $r[2],
                'status' => $r[3], 'birth_weight_g' => $birth,
                'notes' => $i === 0 ? 'Demo note: first born, very vocal.' : null,
            ]);

            $grams = $birth;
            for ($w = 0; $w <= $weeks; $w++) {
                $puppy->weights()->create([
                    'weighed_on' => $litter->born_on->copy()->addWeeks($w),
                    'weight_g' => $grams = (int) ($w === 0 ? $birth : $grams * (1.8 - $w * 0.08) + random_int(-30, 30)),
                ]);
            }
        }
    }

    /** @param array<string, Litter> $l */
    private function diary(array $l): void
    {
        $l['yellow']->diary()->createMany([
            ['posted_on' => now()->subDays(29), 'title' => 'They are here!', 'body' => 'Nine healthy yellow pups arrived overnight. Honey is a champion mom.', 'is_public' => true],
            ['posted_on' => now()->subDays(22), 'title' => 'Week 1', 'body' => 'Everyone has doubled their birth weight. Lots of sleeping and eating.', 'is_public' => true],
            ['posted_on' => now()->subDays(15), 'title' => 'Eyes are opening', 'body' => 'Little blue-grey eyes peeking out. Ears are next.', 'is_public' => true],
            ['posted_on' => now()->subDays(8), 'title' => 'First wobbly steps', 'body' => 'They are exploring the whelping box and falling over each other.', 'is_public' => true],
            ['posted_on' => now()->subDays(2), 'title' => null, 'body' => 'Private reminder: first deworming Thursday, start gruel on Friday.', 'is_public' => false],
        ]);
        $l['fresh']->diary()->createMany([
            ['posted_on' => now()->subDays(9), 'title' => 'Six pups!', 'body' => 'Maple had four black and two chocolate pups, all nursing well.', 'is_public' => true],
            ['posted_on' => now()->subDays(4), 'title' => 'Growing fast', 'body' => 'Bellies are round and everybody has gained weight.', 'is_public' => true],
        ]);
    }

    /** @return array<string, Contact> */
    private function contacts(): array
    {
        $c = [];
        $people = [
            ['jane', 'Jane Miller', ContactType::Buyer, 'jane@example.com', 'Boise', 'ID'],
            ['tom', 'Tom & Linda Harper', ContactType::Buyer, 'harpers@example.com', 'Meridian', 'ID'],
            ['sara', 'Sara Whitfield', ContactType::Buyer, 'sara.w@example.com', 'Nampa', 'ID'],
            ['mike', 'Mike Delgado', ContactType::Buyer, 'mdelgado@example.com', 'Eagle', 'ID'],
            ['priya', 'Priya Natarajan', ContactType::Buyer, 'priya@example.com', 'Boise', 'ID'],
            ['carl', 'Carl Jensen', ContactType::Buyer, 'carl.j@example.com', 'Twin Falls', 'ID'],
            ['beth', 'Beth Ortiz', ContactType::Buyer, 'beth.o@example.com', 'Caldwell', 'ID'],
            ['dan', 'Dan Whitaker', ContactType::Buyer, 'dan@example.com', 'Kuna', 'ID'],
            ['rosa', 'Rosa Bianchi', ContactType::Buyer, 'rosa@example.com', 'Boise', 'ID'],
            ['greg', 'Greg Sorensen', ContactType::Buyer, 'greg.s@example.com', 'Idaho Falls', 'ID'],
            ['allen', 'Dr. Allen (Riverbend Vet)', ContactType::Vet, null, 'Boise', 'ID'],
            ['ridge', 'Ridgeline Retrievers', ContactType::StudOwner, 'ridgeline@example.com', 'Bend', 'OR'],
            ['feed', 'Valley Feed & Supply', ContactType::Business, null, 'Meridian', 'ID'],
            ['nell', 'Aunt Nell', ContactType::Friend, null, 'Pocatello', 'ID'],
        ];

        foreach ($people as $i => [$key, $name, $type, $email, $city, $state]) {
            $c[$key] = Contact::create([
                'name' => $name, 'type' => $type, 'email' => $email,
                'phone' => sprintf('(555) 010-%04d', 100 + $i), 'city' => $city, 'state' => $state,
            ]);
        }

        $c['jane']->contactNotes()->createMany([
            ['body' => 'Called about a yellow female. Wants a hunting companion, has two kids.', 'noted_at' => now()->subDays(20)],
            ['body' => 'Sent deposit by Venmo. Added to the yellow litter list.', 'noted_at' => now()->subDays(12)],
            ['body' => 'Texted photos of the house and yard. Fenced, half acre.', 'noted_at' => now()->subDays(2)],
        ]);
        $c['tom']->contactNotes()->create(['body' => 'Had a Lab for 12 years, lost her last spring. Gentle, quiet home.', 'noted_at' => now()->subDays(9)]);
        $c['allen']->contactNotes()->create(['body' => 'Puppy checks booked for the Thursday before go-home day.', 'noted_at' => now()->subDays(3)]);
        $c['ridge']->contactNotes()->create(['body' => 'Possible outside stud for Honey next year. Their male is Black, OFA excellent.', 'noted_at' => now()->subDays(30)]);

        return $c;
    }

    /**
     * Past buyers for the all-home litters, plus buyers for the reserved puppies.
     *
     * @param  array<string, Litter>  $l
     * @param  array<string, Contact>  $c
     */
    private function homes(array $l, array $c): void
    {
        $buyers = array_values(array_filter($c, fn (Contact $x) => $x->type === ContactType::Buyer));

        foreach ($l['old']->puppies->merge($l['summer']->puppies)->where('status', PuppyStatus::Home)->values() as $i => $puppy) {
            $puppy->update(['contact_id' => $buyers[$i % count($buyers)]->id, 'sale_price' => 1400 + ($i % 3) * 100]);
        }

        foreach ($l['yellow']->puppies->where('status', PuppyStatus::Reserved)->values() as $i => $puppy) {
            $puppy->update(['contact_id' => $buyers[$i]->id, 'sale_price' => 1600]);
        }
    }

    /**
     * @param  array<string, Litter>  $l
     * @param  array<string, Contact>  $c
     */
    private function waitlist(array $l, array $c): void
    {
        $make = fn (array $a) => WaitlistEntry::create($a);

        $make(['contact_id' => $c['jane']->id, 'litter_id' => $l['yellow']->id, 'preferred_sex' => DogSex::Female, 'preferred_color' => CoatColor::Yellow, 'pick_order' => 1, 'status' => WaitlistStatus::DepositPaid, 'deposit_amount' => 300, 'deposit_paid_on' => now()->subDays(12)]);
        $make(['contact_id' => $c['tom']->id, 'litter_id' => $l['yellow']->id, 'preferred_sex' => DogSex::Female, 'preferred_color' => CoatColor::Yellow, 'pick_order' => 2, 'status' => WaitlistStatus::DepositPaid, 'deposit_amount' => 300, 'deposit_paid_on' => now()->subDays(10)]);
        $make(['contact_id' => $c['sara']->id, 'litter_id' => $l['yellow']->id, 'preferred_sex' => DogSex::Male, 'preferred_color' => CoatColor::Yellow, 'pick_order' => 3, 'status' => WaitlistStatus::DepositPaid, 'deposit_amount' => 300, 'deposit_paid_on' => now()->subDays(8)]);
        $make(['contact_id' => $c['mike']->id, 'litter_id' => $l['yellow']->id, 'preferred_sex' => DogSex::Male, 'pick_order' => 4, 'status' => WaitlistStatus::DepositPaid, 'deposit_amount' => 300, 'deposit_paid_on' => now()->subDays(5)]);
        $make(['contact_id' => $c['priya']->id, 'litter_id' => $l['expected']->id, 'preferred_color' => CoatColor::Chocolate, 'pick_order' => 1, 'status' => WaitlistStatus::Waiting, 'notes' => 'Wants a chocolate male if possible.']);
        $make(['contact_id' => $c['carl']->id, 'litter_id' => $l['expected']->id, 'preferred_sex' => DogSex::Female, 'pick_order' => 2, 'status' => WaitlistStatus::Waiting]);
        $make(['contact_id' => $c['beth']->id, 'litter_id' => $l['expected']->id, 'preferred_color' => CoatColor::Yellow, 'pick_order' => 3, 'status' => WaitlistStatus::Contacted, 'notes' => 'Left a voicemail; she will call back this weekend.']);
        $make(['contact_id' => $c['dan']->id, 'litter_id' => $l['planned']->id, 'preferred_color' => CoatColor::Black, 'pick_order' => 1, 'status' => WaitlistStatus::Waiting]);
        $make(['contact_id' => $c['rosa']->id, 'preferred_sex' => DogSex::Female, 'status' => WaitlistStatus::Passed, 'notes' => 'Decided to wait until next year.']);

        foreach ([
            [$c['greg'], DogSex::Male, CoatColor::Black, ['Home and family' => 'Two adults, big fenced yard, work from home.', 'Experience with dogs' => 'Raised Labs growing up.', 'What they want a Lab for' => 'Duck hunting and a family dog.', 'How they heard about us' => 'A friend who has one of your pups.']],
            [$c['nell'], null, null, ['Home and family' => 'Retired couple, house with a yard.', 'Message' => 'Can we visit before the next litter?']],
        ] as [$contact, $sex, $color, $answers]) {
            $make(['contact_id' => $contact->id, 'preferred_sex' => $sex, 'preferred_color' => $color, 'status' => WaitlistStatus::New, 'source' => 'website', 'application' => $answers]);
        }
    }

    private function testimonials(): void
    {
        foreach ([
            ['The Harpers', 'Meridian, ID', 'Cocoa', 'Cocoa was calm, healthy and easy to train from day one. We still get photo updates from the family.'],
            ['Sara W.', 'Nampa, ID', 'Raven', 'We visited twice before choosing and were never rushed. Raven is the best dog we have ever had.'],
            ['Mike D.', 'Eagle, ID', 'Moose', 'Great communication and a wonderful pup. He is now a certified therapy dog.'],
            ['Priya N.', 'Boise, ID', null, 'Warm, honest people, and the puppies were raised in the home. It showed.'],
        ] as $i => [$name, $location, $puppy, $body]) {
            Testimonial::create(['name' => $name, 'location' => $location, 'puppy_name' => $puppy, 'body' => $body, 'is_public' => true, 'sort_order' => $i]);
        }
    }

    /** Extra photos that show up only in the gallery. */
    private function gallery(): void
    {
        $dog = Dog::where('name', 'Tucker')->first();
        $this->photo($dog, 'yellow-a11', 'Tucker in the snow', true);
        $this->photo(Dog::where('name', 'Honey')->first(), 'lab-p2', null, true);
        $this->photo(Dog::where('name', 'Duke')->first(), 'choc-ball', 'Duke relaxing by the fire', true);
    }

    private function photo(Model $owner, string $file, ?string $caption, bool $gallery): void
    {
        $path = database_path("seeders/demo-photos/$file.jpg");
        $upload = new UploadedFile($path, "$file.jpg", 'image/jpeg', null, true);
        $stored = app(ImageStore::class)->store($upload);

        $owner->photos()->create($stored + [
            'caption' => $caption,
            'credit' => 'Wikimedia Commons (CC0/PD)',
            'is_public' => true,
            'in_gallery' => $gallery,
        ]);
    }
}
