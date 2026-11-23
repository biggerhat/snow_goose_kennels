<?php

use App\Models\Document;
use App\Models\Dog;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// The Backblaze disks are swapped for in-memory folders: the app only talks to "a disk", so this proves it
// uses the ones named in the settings and not a hard-coded folder.
beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('b2_photos');
    Storage::fake('b2_documents');
    $this->actingAs(User::factory()->create());
});

it('stores new photos on the photo disk named in settings', function () {
    config(['kennel.photo_disk' => 'b2_photos']);

    $this->post(route('admin.photos.store'), ['photos' => [UploadedFile::fake()->image('a.jpg', 1200, 800)]])->assertRedirect();

    $photo = Photo::firstOrFail();
    Storage::disk('b2_photos')->assertExists([$photo->path, $photo->thumb_path]);
    Storage::disk('public')->assertMissing($photo->path);
});

it('stores new documents on the document disk, serves them only to the owner, and deletes them there', function () {
    config(['kennel.document_disk' => 'b2_documents']);
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);

    $this->post(route('admin.documents.store'), ['file' => UploadedFile::fake()->create('r.pdf', 50, 'application/pdf'), 'documentable_type' => 'dog', 'documentable_id' => $dog->id, 'category' => 'registration'])->assertSessionHasNoErrors();

    $doc = Document::firstOrFail();
    Storage::disk('b2_documents')->assertExists($doc->path);
    Storage::disk('local')->assertMissing($doc->path);
    $this->get(route('admin.documents.show', $doc))->assertOk();

    auth()->logout();
    $this->get(route('admin.documents.show', $doc))->assertRedirect();

    $this->actingAs(User::first());
    $doc->delete();
    Storage::disk('b2_documents')->assertMissing($doc->path);
});

it('copies existing files to the new disks, keeps the originals unless told otherwise, and can be run twice', function () {
    $photo = Photo::create(['path' => 'photos/2026/11/a.jpg', 'thumb_path' => 'photos/2026/11/a_thumb.jpg', 'is_public' => true]);
    Storage::disk('public')->put($photo->path, 'full');
    Storage::disk('public')->put($photo->thumb_path, 'thumb');
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    $doc = $dog->documents()->create(['category' => 'registration', 'title' => 'Reg', 'path' => 'documents/2026/11/r.pdf', 'original_name' => 'r.pdf', 'mime' => 'application/pdf', 'size' => 3]);
    Storage::disk('local')->put($doc->path, 'pdf');

    config(['kennel.photo_disk' => 'b2_photos', 'kennel.document_disk' => 'b2_documents']);

    $this->artisan('kennel:move-files')->assertSuccessful();
    Storage::disk('b2_photos')->assertExists([$photo->path, $photo->thumb_path]);
    Storage::disk('b2_documents')->assertExists($doc->path);
    Storage::disk('public')->assertExists($photo->path);
    expect(Storage::disk('b2_documents')->get($doc->path))->toBe('pdf');

    $this->artisan('kennel:move-files', ['--delete-source' => true])->assertSuccessful();
    Storage::disk('public')->assertMissing($photo->path);
    Storage::disk('local')->assertMissing($doc->path);
    Storage::disk('b2_photos')->assertExists($photo->path);
});

it('does nothing when the files are already where settings say', function () {
    $this->artisan('kennel:move-files')->expectsOutputToContain('already on')->assertSuccessful();
});

it('writes to the public photo bucket as public and to the documents bucket as private', function () {
    // A public Backblaze bucket refuses the 'private' ACL that is Flysystem's default, so this must stay explicit.
    expect(config('filesystems.disks.b2_photos.visibility'))->toBe('public')
        ->and(config('filesystems.disks.b2_documents.visibility'))->toBe('private');
});
