<?php

use App\Models\Album;
use App\Models\Dog;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

function makePhoto(array $attrs = []): Photo
{
    return Photo::create($attrs + ['path' => 'p/a.jpg', 'thumb_path' => 'p/a_t.jpg']);
}

test('albums can be created, renamed and deleted without losing photos', function () {
    $this->post(route('admin.albums.store'), ['name' => 'Summer litter'])->assertRedirect();
    $album = Album::firstOrFail();

    $this->put(route('admin.albums.update', $album), ['name' => 'Summer 2026', 'description' => 'Whelping box'])->assertRedirect();
    expect($album->fresh()->name)->toBe('Summer 2026');

    $photo = makePhoto(['album_id' => $album->id]);
    $this->delete(route('admin.albums.destroy', $album))->assertRedirect();

    expect(Album::count())->toBe(0)->and($photo->fresh()->album_id)->toBeNull();
});

test('an album needs a name', function () {
    $this->post(route('admin.albums.store'), ['name' => ''])->assertSessionHasErrors('name');
});

test('photos upload into an album and can be kept private', function () {
    $album = Album::create(['name' => 'Vet visits']);

    $this->post(route('admin.photos.store'), [
        'photos' => [UploadedFile::fake()->image('a.jpg', 800, 600)],
        'album_id' => $album->id,
        'is_public' => false,
        'taken_on' => '2026-05-01',
    ])->assertRedirect();

    $photo = Photo::firstOrFail();
    expect($photo->album_id)->toBe($album->id)
        ->and($photo->is_public)->toBeFalse()
        ->and($photo->taken_on->toDateString())->toBe('2026-05-01');
});

test('private photos stay off the public gallery even in an album', function () {
    $album = Album::create(['name' => 'Private']);
    makePhoto(['album_id' => $album->id, 'caption' => 'SECRET-PIC', 'is_public' => false, 'in_gallery' => true]);
    makePhoto(['caption' => 'VISIBLE-PIC', 'is_public' => true, 'in_gallery' => true, 'path' => 'p/b.jpg', 'thumb_path' => 'p/b_t.jpg']);

    $html = $this->get(route('site.gallery'))->getContent();

    expect($html)->toContain('VISIBLE-PIC')->not->toContain('SECRET-PIC');
});

test('the library filters by album, visibility and link', function () {
    $album = Album::create(['name' => 'Puppies']);
    $dog = Dog::create(['name' => 'Bella', 'sex' => 'female', 'role' => 'dam']);
    makePhoto(['album_id' => $album->id, 'is_public' => false, 'caption' => 'in-album']);
    makePhoto(['caption' => 'loose', 'is_public' => true]);
    makePhoto(['caption' => 'dog-pic', 'photoable_type' => 'dog', 'photoable_id' => $dog->id]);

    $captions = fn (array $q) => collect($this->get(route('admin.photos.index', $q))->viewData('page')['props']['photos']['data'])->pluck('caption')->sort()->values()->all();

    expect($captions(['album' => $album->id]))->toBe(['in-album'])
        ->and($captions(['album' => 'none']))->toBe(['dog-pic', 'loose'])
        ->and($captions(['show' => 'private']))->toBe(['in-album'])
        ->and($captions(['subject' => 'dog']))->toBe(['dog-pic'])
        ->and($captions(['subject' => 'none']))->toBe(['in-album', 'loose'])
        ->and($captions(['q' => 'loose']))->toBe(['loose']);

    $page = $this->get(route('admin.photos.index'))->viewData('page')['props'];
    expect($page['totals'])->toMatchArray(['all' => 3, 'private' => 1, 'unsorted' => 2])
        ->and($page['photos']['data'][0]['subject'])->toBe('Bella');
});

test('bulk actions move, hide, show and delete photos', function () {
    $album = Album::create(['name' => 'Keepers']);
    $a = makePhoto();
    $b = makePhoto(['path' => 'p/b.jpg', 'thumb_path' => 'p/b_t.jpg']);
    $ids = [$a->id, $b->id];

    $this->post(route('admin.photos.bulk'), ['ids' => $ids, 'action' => 'move', 'album_id' => $album->id])->assertRedirect();
    expect(Photo::where('album_id', $album->id)->count())->toBe(2);

    $this->post(route('admin.photos.bulk'), ['ids' => $ids, 'action' => 'hide'])->assertRedirect();
    expect(Photo::where('is_public', false)->count())->toBe(2);

    $this->post(route('admin.photos.bulk'), ['ids' => $ids, 'action' => 'show'])->assertRedirect();
    expect(Photo::where('is_public', true)->count())->toBe(2);

    $this->post(route('admin.photos.bulk'), ['ids' => $ids, 'action' => 'move'])->assertRedirect();
    expect(Photo::whereNotNull('album_id')->count())->toBe(0);

    $this->post(route('admin.photos.bulk'), ['ids' => $ids, 'action' => 'delete'])->assertRedirect();
    expect(Photo::count())->toBe(0);
});

test('bulk actions reject unknown actions and empty selections', function () {
    $this->post(route('admin.photos.bulk'), ['ids' => [], 'action' => 'move'])->assertSessionHasErrors('ids');
    $this->post(route('admin.photos.bulk'), ['ids' => [1], 'action' => 'explode'])->assertSessionHasErrors('action');
});

test('guests cannot use the album or bulk routes', function () {
    auth()->logout();

    $this->post(route('admin.albums.store'), ['name' => 'x'])->assertRedirect(route('login'));
    $this->post(route('admin.photos.bulk'), ['ids' => [1], 'action' => 'delete'])->assertRedirect(route('login'));
});
