<?php

use App\Models\Contact;
use App\Models\Document;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

function upload(array $over = [], ?UploadedFile $file = null)
{
    $dog = $over['dog'] ?? Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    unset($over['dog']);

    return [$dog, test()->post(route('admin.documents.store'), $over + [
        'file' => $file ?? UploadedFile::fake()->create('registration.pdf', 120, 'application/pdf'),
        'documentable_type' => 'dog',
        'documentable_id' => $dog->id,
        'category' => 'registration',
    ])];
}

test('a document is stored privately and tied to its dog', function () {
    [$dog, $response] = upload(['title' => 'AKC certificate', 'issued_on' => '2024-05-01', 'expires_on' => '2030-05-01']);
    $response->assertSessionHasNoErrors();

    $doc = $dog->documents()->firstOrFail();
    expect($doc->title)->toBe('AKC certificate')->and($doc->category)->toBe('registration')->and($doc->original_name)->toBe('registration.pdf');
    Storage::disk('local')->assertExists($doc->path);
    Storage::disk('public')->assertMissing($doc->path);
});

test('the title defaults to the file name', function () {
    [$dog] = upload();

    expect($dog->documents()->first()->title)->toBe('registration');
});

test('only allowed file types are accepted', function () {
    [, $response] = upload(file: UploadedFile::fake()->createWithContent('evil.php', '<?php echo 1;'));
    $response->assertSessionHasErrors('file');

    [, $response] = upload(file: UploadedFile::fake()->create('run.exe', 10));
    $response->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

test('a document can be attached to a dog, litter, puppy or contact', function () {
    $litter = Litter::create(['status' => 'expected']);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $contact = Contact::create(['name' => 'Jane', 'type' => 'buyer']);

    foreach ([['litter', $litter], ['puppy', $puppy], ['contact', $contact]] as [$type, $owner]) {
        $this->post(route('admin.documents.store'), [
            'file' => UploadedFile::fake()->create("$type.pdf", 10, 'application/pdf'),
            'documentable_type' => $type, 'documentable_id' => $owner->id, 'category' => 'other',
        ])->assertSessionHasNoErrors();
        expect($owner->documents()->count())->toBe(1);
    }
});

test('a PDF opens in the browser and other files download, never public', function () {
    [$dog] = upload();
    $doc = $dog->documents()->first();

    $response = $this->get(route('admin.documents.show', $doc))->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    [$dog2] = upload(file: UploadedFile::fake()->create('terms.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
    $docx = $dog2->documents()->latest('id')->first();
    expect($this->get(route('admin.documents.show', $docx))->headers->get('Content-Disposition'))->toStartWith('attachment');
});

test('visitors and logged-out users cannot reach documents', function () {
    [$dog] = upload();
    $doc = $dog->documents()->first();

    $url = route('admin.documents.show', $doc);
    auth()->logout();
    $this->get($url)->assertRedirect(route('login'));
    $this->post(route('admin.documents.store'), [])->assertRedirect(route('login'));
});

test('the stored path is never sent to the browser', function () {
    [$dog] = upload();

    $props = $this->get(route('admin.dogs.edit', $dog))->viewData('page')['props'];
    $json = json_encode($props['dog']['documents']);

    expect($json)->not->toContain('documents\/20')->not->toContain('"path"')->and($props['dog']['documents'][0]['url'])->toContain('/admin/documents/');
});

test('editing changes the details and deleting removes the file', function () {
    [$dog] = upload();
    $doc = $dog->documents()->first();

    $this->put(route('admin.documents.update', $doc), ['title' => 'Renamed', 'category' => 'pedigree', 'expires_on' => '2031-01-01'])->assertSessionHasNoErrors();
    expect($doc->fresh()->title)->toBe('Renamed')->and($doc->fresh()->category)->toBe('pedigree');

    $this->put(route('admin.documents.update', $doc), ['title' => 'x', 'category' => 'pedigree', 'issued_on' => '2025-01-01', 'expires_on' => '2024-01-01'])->assertSessionHasErrors('expires_on');

    $this->delete(route('admin.documents.destroy', $doc))->assertRedirect();
    Storage::disk('local')->assertMissing($doc->path);
    expect(Document::count())->toBe(0);
});

test('deleting a dog, litter or contact removes their files', function () {
    [$dog] = upload();
    $litter = Litter::create(['status' => 'on_ground', 'born_on' => today()->subWeek()]);
    $puppy = $litter->puppies()->create(['sex' => 'male', 'status' => 'available']);
    $this->post(route('admin.documents.store'), ['file' => UploadedFile::fake()->create('sale.pdf', 10, 'application/pdf'), 'documentable_type' => 'puppy', 'documentable_id' => $puppy->id, 'category' => 'sale']);

    $paths = Document::pluck('path');
    expect($paths)->toHaveCount(2);

    $this->delete(route('admin.dogs.destroy', $dog));
    $this->delete(route('admin.litters.destroy', $litter));

    expect(Document::count())->toBe(0);
    foreach ($paths as $p) {
        Storage::disk('local')->assertMissing($p);
    }
});
