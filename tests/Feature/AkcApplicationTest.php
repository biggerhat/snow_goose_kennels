<?php

use App\Models\Contact;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Setting;
use App\Models\User;
use App\Support\AkcLitterApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());
});

function kennelOwner(): void
{
    foreach (['owner_first_name' => 'Mary', 'owner_last_name' => 'Smith', 'owner_address' => '12 Elm St', 'owner_city' => 'Boise', 'owner_state' => 'ID', 'owner_zip' => '83702-1234', 'owner_phone' => '(208) 555-0142', 'owner_email' => 'mary@example.com'] as $k => $v) {
        Setting::put($k, $v);
    }
}

function applicationLitter(): Litter
{
    $owner = Contact::create(['name' => 'Ridgeline Retrievers', 'type' => 'stud_owner', 'phone' => '1-541-555-0199', 'email' => 'r@example.com']);
    $sire = Dog::create(['name' => 'Duke', 'registered_name' => 'Ridgeline Duke of Bend', 'sex' => 'male', 'role' => 'other', 'is_ours' => false, 'owner_contact_id' => $owner->id, 'registry_number' => 'SR111']);
    $dam = Dog::create(['name' => 'Bella', 'registered_name' => "Snow Goose's Bella", 'sex' => 'female', 'role' => 'dam', 'registry_number' => 'SR222', 'co_owner_name' => 'Pat Lee Jones']);
    $litter = Litter::create(['status' => 'on_ground', 'sire_id' => $sire->id, 'dam_id' => $dam->id, 'born_on' => '2026-04-05']);
    $litter->puppies()->createMany([['sex' => 'male', 'status' => 'available'], ['sex' => 'male', 'status' => 'available'], ['sex' => 'female', 'status' => 'available']]);

    return $litter;
}

test('the application is filled in from what is on file', function () {
    kennelOwner();
    $f = AkcLitterApplication::build(applicationLitter())['fields'];

    expect($f)->toMatchArray([
        'Month' => '04', 'Day' => '05', 'Year' => '2026', 'breed_description' => 'Labrador Retriever',
        'Text4' => '2', 'Text5' => '1', 'Text7' => '3',
        'sire_number' => 'SR111', 'text_sire_name_full' => 'Ridgeline Duke of Bend', 'name1' => 'Ridgeline', 'name2' => 'Retrievers',
        'phone1' => '541', 'phone2' => '555', 'phone3' => '0199', 'email1' => 'r@example.com',
        'dam_number' => 'SR222', 'text_dam_name_full' => "Snow Goose's Bella", 'name3' => 'Mary', 'name4' => 'Smith',
        'address1line1' => '12 Elm St', 'city1' => 'Boise', 'cde_state_province' => 'ID', 'cde_postal_code' => '83702', 'postalcode1' => '1234',
        'phone4' => '208', 'phone5' => '555', 'phone6' => '0142', 'email2' => 'mary@example.com',
        'name5' => 'Pat Lee', 'name6' => 'Jones',
    ]);
});

test('missing information is listed instead of guessed', function () {
    $litter = Litter::create(['status' => 'expected']);

    $missing = collect(AkcLitterApplication::build($litter)['missing'])->pluck('text')->join('|');

    expect($missing)->toContain('born')->toContain('puppies')->toContain('sire')->toContain('dam');
});

test('names and phone numbers are split sensibly', function () {
    expect(AkcLitterApplication::splitName('Jane Q Public'))->toBe(['Jane Q', 'Public'])
        ->and(AkcLitterApplication::splitName('Cher'))->toBe(['Cher', ''])
        ->and(AkcLitterApplication::splitPhone('+1 (208) 555-0142'))->toBe(['208', '555', '0142'])
        ->and(AkcLitterApplication::splitPhone('555-0142'))->toBe(['', '', '']);
});

test('the application page shows the worksheet', function () {
    kennelOwner();
    $litter = applicationLitter();

    $props = $this->get(route('admin.litters.akc', $litter))->assertOk()->viewData('page')['props'];

    expect($props['application']['fields']['sire_number'])->toBe('SR111')->and($props['application']['sections'])->toHaveCount(3)->and($props['onlineUrl'])->toContain('akc.org');
});

test('AKC form is fetched once, kept, and served only to the owner', function () {
    Http::fake(['images.akc.org/*' => Http::response("%PDF-1.6\nfake", 200)]);

    $this->get(route('admin.akc.litter-form'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('admin.akc.litter-form'))->assertOk();
    Http::assertSentCount(1);

    auth()->logout();
    $this->get(route('admin.akc.litter-form'))->assertRedirect(route('login'));
});

test('a clear error is returned when AKC cannot be reached and nothing is cached', function () {
    Http::fake(['images.akc.org/*' => Http::response('nope', 500)]);

    $this->get(route('admin.akc.litter-form'))->assertStatus(502);
});

test('a non-PDF answer from AKC is never stored', function () {
    Http::fake(['images.akc.org/*' => Http::response('<html>hi</html>', 200)]);

    $this->get(route('admin.akc.litter-form'))->assertStatus(502);
    Storage::disk('local')->assertMissing('akc/litter-application.pdf');
});

test('owner details are saved privately and validated', function () {
    $this->put(route('admin.site.update'), ['owner_first_name' => 'Mary', 'owner_state' => 'Idaho', 'owner_zip' => 'abc', 'owner_phone' => '12'])
        ->assertSessionHasErrors(['owner_state', 'owner_zip', 'owner_phone']);

    $this->put(route('admin.site.update'), ['owner_first_name' => 'Mary', 'owner_last_name' => 'Smith', 'owner_state' => 'ID', 'owner_zip' => '83702'])->assertSessionHasNoErrors();
    expect(Setting::get('owner_first_name'))->toBe('Mary');

    $html = $this->get(route('site.contact'))->getContent();
    expect($html)->not->toContain('Mary');
});
