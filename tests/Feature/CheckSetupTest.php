<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('kennel:check fails and says how to fix it when there is no owner login', function () {
    $this->artisan('kennel:check')
        ->expectsOutputToContain('kennel:owner')
        ->assertFailed();
});

test('kennel:check flags the demo user left on a server', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->artisan('kennel:check')
        ->expectsOutputToContain('Delete the demo user')
        ->assertFailed();
});

test('kennel:check reports the scheduler as running once cron has fired', function () {
    User::factory()->create(['email' => 'owner@example.com']);
    Cache::forever('kennel.heartbeat', now()->toIso8601String());

    $this->artisan('kennel:check')->doesntExpectOutputToContain('Add the cron line');
});
