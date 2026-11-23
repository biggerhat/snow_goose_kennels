<?php

use App\Models\Dog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('every page carries browser protection headers', function () {
    $r = $this->get('/');

    expect($r->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($r->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($r->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($r->headers->get('Permissions-Policy'))->toContain('camera=()')
        ->and($r->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'");
});

test('private documents keep their own stricter policy', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    $this->post(route('admin.documents.store'), ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'documentable_type' => 'dog', 'documentable_id' => $dog->id, 'category' => 'other']);

    $r = $this->get(route('admin.documents.show', $dog->documents()->first()));

    // A sandbox policy stops the browser's own PDF viewer from opening the file, so PDFs go without it.
    expect($r->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($r->headers->get('Content-Security-Policy'))->not->toContain('sandbox');

    $this->post(route('admin.documents.store'), ['file' => UploadedFile::fake()->image('scan.png'), 'documentable_type' => 'dog', 'documentable_id' => $dog->id, 'category' => 'other']);
    $image = $dog->documents()->where('mime', 'image/png')->first();

    expect($this->get(route('admin.documents.show', $image))->headers->get('Content-Security-Policy'))->toContain('sandbox');
});

test('a missing page shows the branded error page', function () {
    $this->get('/no-such-page')->assertNotFound()->assertSee('We can’t find that page', false)->assertSee('Back to the home page', false);
});

test('a hidden dog is a branded 404 too', function () {
    $dog = Dog::create(['name' => 'Secret', 'sex' => 'male', 'role' => 'stud', 'is_public' => false]);

    $this->get(route('site.dog', $dog))->assertNotFound()->assertSee('We can’t find that page', false);
});

test('the session lasts a working day', function () {
    expect(file_get_contents(base_path('.env.example')))->toContain('SESSION_LIFETIME=480');
});
