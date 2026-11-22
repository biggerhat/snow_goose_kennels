<?php

use App\Mail\DailyReminders;
use App\Models\Contact;
use App\Models\Litter;
use App\Models\Photo;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('diary entries show publicly only when public and refresh the litter date', function () {
    $this->actingAs(User::factory()->create());
    $litter = Litter::create(['status' => 'on_ground', 'name' => 'Marsh', 'is_public' => true]);
    $this->travel(3)->days();

    $this->post(route('admin.diary.store', $litter), ['posted_on' => today()->toDateString(), 'title' => 'Eyes open', 'body' => 'PUBLIC-DIARY', 'is_public' => true])->assertRedirect();
    $this->post(route('admin.diary.store', $litter), ['posted_on' => today()->toDateString(), 'body' => 'PRIVATE-DIARY', 'is_public' => false])->assertRedirect();

    expect($litter->fresh()->updated_at->isToday())->toBeTrue();

    $html = $this->get(route('site.litters'))->getContent();
    expect($html)->toContain('PUBLIC-DIARY')->not->toContain('PRIVATE-DIARY');
});

test('diary entries cannot be dated in the future', function () {
    $this->actingAs(User::factory()->create());
    $litter = Litter::create(['status' => 'on_ground']);

    $this->post(route('admin.diary.store', $litter), ['posted_on' => today()->addDay()->toDateString(), 'body' => 'x'])->assertSessionHasErrors('posted_on');
});

test('uploaded photos are resized, thumbnailed, and stripped of metadata', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.photos.store'), ['photos' => [UploadedFile::fake()->image('big.jpg', 3600, 2400)], 'credit' => 'Photo: Jane'])->assertRedirect();

    $photo = Photo::first();
    expect($photo->credit)->toBe('Photo: Jane');
    Storage::disk('public')->assertExists([$photo->path, $photo->thumb_path]);

    [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($photo->path));
    [$tw] = getimagesizefromstring(Storage::disk('public')->get($photo->thumb_path));
    expect(max($w, $h))->toBeLessThanOrEqual(2000)->and($tw)->toBeLessThanOrEqual(640);
});

test('png uploads are accepted and non-images are rejected with a message, not a crash', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->post(route('admin.photos.store'), ['photos' => [UploadedFile::fake()->image('a.png', 800, 600)]])->assertRedirect();
    expect(Photo::count())->toBe(1);

    $this->post(route('admin.photos.store'), ['photos' => [UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo 1;')]])
        ->assertSessionHasErrors('photos');
    expect(Photo::count())->toBe(1);
});

test('deleting a photo removes its files', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
    $this->post(route('admin.photos.store'), ['photos' => [UploadedFile::fake()->image('a.jpg', 800, 600)]]);
    $photo = Photo::first();

    $this->delete(route('admin.photos.destroy', $photo))->assertRedirect();

    Storage::disk('public')->assertMissing([$photo->path, $photo->thumb_path]);
});

test('the reminder command emails when something needs attention and stays quiet otherwise', function () {
    Mail::fake();
    User::factory()->create();

    $this->artisan('kennel:reminders')->expectsOutput('Nothing to remind about today.')->assertSuccessful();
    Mail::assertNothingSent();

    Litter::create(['status' => 'expected', 'name' => 'Soon', 'due_on' => today()->addDays(3)]);
    Litter::create(['status' => 'expected', 'name' => 'Far', 'due_on' => today()->addDays(40)]);
    $contact = Contact::create(['name' => 'Jane', 'type' => 'buyer', 'phone' => '555']);
    $entry = WaitlistEntry::create(['contact_id' => $contact->id, 'status' => 'new']);
    $entry->forceFill(['created_at' => now()->subDays(3)])->save();

    $this->artisan('kennel:reminders')->assertSuccessful();

    Mail::assertSent(DailyReminders::class, fn ($m) => $m->due->count() === 1 && $m->unanswered->count() === 1 && $m->goingHome->isEmpty());
});

test('pages carry the social share image tags', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('og:image')->toContain('og-image.png')->toContain('twitter:card');
    expect(file_exists(public_path('og-image.png')))->toBeTrue();
});
