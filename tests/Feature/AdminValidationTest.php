<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Photo;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function dog(string $name, string $sex = 'female', string $role = 'dam'): Dog
{
    return Dog::create(['name' => $name, 'sex' => $sex, 'role' => $role]);
}

function litterData(array $over = []): array
{
    return $over + ['status' => 'planned'];
}

test('prices typed the way people write them are accepted', function () {
    $this->post(route('admin.litters.store'), litterData(['price' => '$1,500', 'deposit' => '300.00']))->assertSessionHasNoErrors();

    $litter = Litter::firstOrFail();
    expect($litter->price)->toBe(1500)->and($litter->deposit)->toBe(300);
});

test('the deposit cannot be more than the price', function () {
    $this->post(route('admin.litters.store'), litterData(['price' => 1000, 'deposit' => 1500]))
        ->assertSessionHasErrors(['deposit' => 'The deposit is more than the price. Check both amounts.']);
});

test('a litter needs a male sire and a female dam', function () {
    $bella = dog('Bella');
    $duke = dog('Duke', 'male', 'stud');

    $this->post(route('admin.litters.store'), litterData(['sire_id' => $bella->id, 'dam_id' => $duke->id]))
        ->assertSessionHasErrors(['sire_id', 'dam_id']);

    $this->post(route('admin.litters.store'), litterData(['sire_id' => $duke->id, 'dam_id' => $bella->id]))
        ->assertSessionHasNoErrors();
});

test('litter dates must make sense together', function () {
    $this->post(route('admin.litters.store'), litterData(['bred_on' => '2026-03-10', 'due_on' => '2026-03-01']))->assertSessionHasErrors('due_on');
    $this->post(route('admin.litters.store'), litterData(['bred_on' => '2026-03-10', 'born_on' => '2026-03-05']))->assertSessionHasErrors('born_on');
    $this->post(route('admin.litters.store'), litterData(['born_on' => '2026-03-10', 'go_home_on' => '2026-03-01']))->assertSessionHasErrors('go_home_on');
    $this->post(route('admin.litters.store'), litterData(['born_on' => now()->addDay()->toDateString()]))->assertSessionHasErrors('born_on');
});

test('a litter marked as born needs a birth date', function () {
    $this->post(route('admin.litters.store'), litterData(['status' => 'on_ground']))->assertSessionHasErrors('born_on');
    $this->post(route('admin.litters.store'), litterData(['status' => 'on_ground', 'born_on' => today()->subWeek()->toDateString()]))->assertSessionHasNoErrors();
});

test('error messages are written in plain words', function () {
    $this->post(route('admin.litters.store'), litterData(['price' => 'lots']))
        ->assertSessionHasErrors(['price' => 'Price should be a whole number, like 1500 (no cents or letters).']);

    $this->post(route('admin.dogs.store'), ['sex' => 'male', 'role' => 'stud'])
        ->assertSessionHasErrors(['name' => 'Name is needed.']);
});

test('dogs: unique name, birth date not in the future, microchip format', function () {
    dog('Bella');

    $this->post(route('admin.dogs.store'), ['name' => 'Bella', 'sex' => 'female', 'role' => 'dam'])->assertSessionHasErrors('name');
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'male', 'role' => 'stud', 'birth_date' => now()->addDay()->toDateString()])->assertSessionHasErrors('birth_date');
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'male', 'role' => 'stud', 'microchip' => 'abc123'])->assertSessionHasErrors('microchip');
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'male', 'role' => 'stud', 'microchip' => '985 112 345 678 901'])->assertSessionHasNoErrors();
    expect(Dog::where('name', 'Ace')->value('microchip'))->toBe('985112345678901');
});

test('a dog can be saved again under its own name', function () {
    $bella = dog('Bella');

    $this->put(route('admin.dogs.update', $bella), ['name' => 'Bella', 'sex' => 'female', 'role' => 'dam', 'bio' => 'Sweet'])->assertSessionHasNoErrors();
});

test('phone numbers need enough digits', function () {
    $this->post(route('admin.contacts.store'), ['name' => 'Jane', 'type' => 'buyer', 'phone' => '12-34'])->assertSessionHasErrors('phone');
    $this->post(route('admin.contacts.store'), ['name' => 'Jane', 'type' => 'buyer', 'phone' => '(208) 555-0123'])->assertSessionHasNoErrors();
});

test('waitlist pick numbers are unique within a litter', function () {
    $litter = Litter::create(['status' => 'expected']);
    $jane = Contact::create(['name' => 'Jane', 'type' => 'buyer']);
    $tom = Contact::create(['name' => 'Tom', 'type' => 'buyer']);
    $entry = WaitlistEntry::create(['contact_id' => $jane->id, 'litter_id' => $litter->id, 'pick_order' => 1, 'status' => 'waiting']);

    $this->post(route('admin.waitlist.store'), ['contact_id' => $tom->id, 'litter_id' => $litter->id, 'pick_order' => 1, 'status' => 'waiting'])
        ->assertSessionHasErrors('pick_order');

    $this->put(route('admin.waitlist.update', $entry), ['contact_id' => $jane->id, 'litter_id' => $litter->id, 'pick_order' => 1, 'status' => 'deposit_paid', 'deposit_amount' => '$300'])
        ->assertSessionHasNoErrors();
    expect($entry->fresh()->deposit_amount)->toBe(300);
});

test('a deposit date needs an amount', function () {
    $tom = Contact::create(['name' => 'Tom', 'type' => 'buyer']);

    $this->post(route('admin.waitlist.store'), ['contact_id' => $tom->id, 'status' => 'deposit_paid', 'deposit_paid_on' => today()->toString()])
        ->assertSessionHasErrors('deposit_amount');
});

test('weigh-ins cannot be dated before the litter was born', function () {
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subDays(3)]);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);

    $this->post(route('admin.weigh-ins.store', $litter), ['weighed_on' => today()->subWeek()->toDateString(), 'weights' => [$puppy->id => 400]])
        ->assertSessionHasErrors('weighed_on');
});

test('deleting a dog or litter keeps its photos in the library', function () {
    $dog = dog('Bella');
    $litter = Litter::create(['status' => 'expected']);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $make = fn ($owner) => $owner->photos()->create(['path' => 'p/a.jpg', 'thumb_path' => 'p/a_t.jpg']);
    $make($dog);
    $make($litter);
    $make($puppy);

    $this->delete(route('admin.dogs.destroy', $dog));
    $this->delete(route('admin.litters.destroy', $litter));

    expect(Photo::count())->toBe(3)->and(Photo::whereNotNull('photoable_type')->count())->toBe(0);
});

test('the help page opens for the owner and not for visitors', function () {
    $this->get(route('admin.help'))->assertOk();

    auth()->logout();
    $this->get(route('admin.help'))->assertRedirect(route('login'));
});

test('the dashboard checklist ticks steps off as the kennel fills in', function () {
    $steps = fn () => collect($this->get(route('dashboard'))->viewData('page')['props']['setup'])->pluck('done', 'key')->all();

    expect($steps())->toMatchArray(['dogs' => false, 'litter' => false, 'photos' => false]);

    dog('Bella');
    Litter::create(['status' => 'planned']);

    expect($steps())->toMatchArray(['dogs' => true, 'litter' => true, 'photos' => false]);
});

test('a stud must be male and a dam must be female', function () {
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'female', 'role' => 'stud'])->assertSessionHasErrors('role');
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'male', 'role' => 'dam'])->assertSessionHasErrors('role');
    $this->post(route('admin.dogs.store'), ['name' => 'Ace', 'sex' => 'male', 'role' => 'stud'])->assertSessionHasNoErrors();
});

test('saving a puppy confirms with a message', function () {
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeek()]);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);

    $this->put(route('admin.puppies.update', $puppy), ['sex' => 'male', 'status' => 'reserved', 'birth_weight_g' => 10])
        ->assertSessionHasErrors(['birth_weight_g' => 'That birth weight looks too small. Newborn Labs are usually 6 to 20 ounces (170 to 570 grams).']);
});
