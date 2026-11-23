<?php

use App\Mail\NewStory;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function story(array $over = []): array
{
    return $over + ['name' => 'The Millers', 'location' => 'Boise, ID', 'puppy_name' => 'Maggie', 'email' => 'mill@example.com', 'body' => 'Maggie is the best thing that ever happened to us.', 'permission' => true];
}

it('shows the share-your-story page to anyone', function () {
    $this->get('/share-your-story')->assertOk()->assertInertia(fn ($p) => $p->component('public/ShareStory'));
});

it('keeps a sent-in story hidden until the owner approves it, and emails the owner', function () {
    Mail::fake();
    User::factory()->create();

    $this->post('/share-your-story', story())->assertRedirect('/share-your-story');

    $t = Testimonial::firstOrFail();
    expect($t->is_public)->toBeFalse()->and($t->needs_review)->toBeTrue();
    Mail::assertSent(NewStory::class);
    $this->get('/')->assertInertia(fn ($p) => $p->has('quotes', 0));
    expect($this->get('/')->getContent())->not->toContain('best thing that ever happened');
});

it('needs permission, a name and a real story', function () {
    $this->post('/share-your-story', story(['permission' => false]))->assertSessionHasErrors('permission');
    $this->post('/share-your-story', story(['name' => '']))->assertSessionHasErrors('name');
    $this->post('/share-your-story', story(['body' => 'Nice']))->assertSessionHasErrors('body');
    expect(Testimonial::count())->toBe(0);
});

it('ignores bots that fill the hidden field', function () {
    $this->post('/share-your-story', story(['website' => 'http://spam.example']))->assertRedirect('/share-your-story');
    expect(Testimonial::count())->toBe(0);
});

it('lets the owner approve a story, which then shows on the home page', function () {
    $t = Testimonial::create(['name' => 'The Millers', 'body' => 'Lovely dog', 'is_public' => false, 'needs_review' => true]);
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.testimonials.index'))->assertOk();
    expect($this->get('/')->viewData('page')['props']['nav']['stories'])->toBe(1);

    $this->post(route('admin.testimonials.approve', $t))->assertRedirect();

    expect($t->fresh()->is_public)->toBeTrue()->and($t->fresh()->needs_review)->toBeFalse();
    $this->get('/')->assertInertia(fn ($p) => $p->has('quotes', 1));
});
