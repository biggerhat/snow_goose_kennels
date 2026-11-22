<?php

use App\Mail\LitterUpdate;
use App\Models\Contact;
use App\Models\Litter;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->litter = Litter::create(['status' => 'on_ground', 'name' => 'Spring']);
    $mk = fn (string $name, ?string $email, string $status = 'waiting') => WaitlistEntry::create([
        'contact_id' => Contact::create(['name' => $name, 'type' => 'buyer', 'email' => $email])->id, 'litter_id' => $this->litter->id, 'status' => $status,
    ]);
    $this->jane = $mk('Jane Miller', 'jane@example.com');
    $this->tom = $mk('Tom Harper', 'tom@example.com');
    $this->noEmail = $mk('Phone Only', null);
    $this->placed = $mk('Already Home', 'home@example.com', 'placed');
});

it('sends one email per person and keeps a record', function () {
    Mail::fake();

    $this->post(route('admin.litter-messages.store', $this->litter), ['subject' => 'The puppies are here!', 'body' => 'Eight healthy pups.', 'entry_ids' => [$this->jane->id, $this->tom->id]])->assertRedirect();

    Mail::assertSent(LitterUpdate::class, 2);
    Mail::assertSent(LitterUpdate::class, fn ($m) => $m->hasTo('jane@example.com') && ! $m->hasTo('tom@example.com'));
    expect($this->litter->messages()->first())->recipient_count->toBe(2)->subject->toBe('The puppies are here!');
    expect($this->jane->contact->contactNotes()->where('body', 'like', '%The puppies are here!%')->exists())->toBeTrue();
});

it('skips people without an email and people already placed or passed', function () {
    Mail::fake();

    $this->post(route('admin.litter-messages.store', $this->litter), ['subject' => 'Hi', 'body' => 'News', 'entry_ids' => [$this->noEmail->id, $this->placed->id]])->assertRedirect();

    Mail::assertNothingSent();
    expect($this->litter->messages()->count())->toBe(0);
});

it('asks for a subject, a message and at least one person in plain words', function () {
    Mail::fake();
    $r = $this->post(route('admin.litter-messages.store', $this->litter), ['subject' => '', 'body' => '', 'entry_ids' => []]);

    $r->assertSessionHasErrors(['subject', 'body', 'entry_ids']);
    expect(session('errors')->first('subject'))->toContain('short subject');
    Mail::assertNothingSent();
});

it('reports a failed send instead of crashing and still sends the rest', function () {
    $sent = 0;
    Mail::shouldReceive('to')->andReturnUsing(function ($address) use (&$sent) {
        $m = Mockery::mock();
        $m->shouldReceive('send')->andReturnUsing(function () use ($address, &$sent) {
            if ($address === 'jane@example.com') {
                throw new RuntimeException('smtp down');
            }
            $sent++;
        });

        return $m;
    });

    $this->post(route('admin.litter-messages.store', $this->litter), ['subject' => 'Hi', 'body' => 'News', 'entry_ids' => [$this->jane->id, $this->tom->id]])->assertRedirect();

    expect($sent)->toBe(1)->and($this->litter->messages()->first()->recipient_count)->toBe(1);
});

it('renders the email with the first name and the reason they are getting it', function () {
    $html = (new LitterUpdate($this->jane->contact, 'Hello', 'Pups are due Friday.', 'Spring'))->render();

    expect($html)->toContain('Hi Jane,')->toContain('Pups are due Friday.')->toContain('asked to be on the list for Spring');
});
