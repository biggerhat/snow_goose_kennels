<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateOwner extends Command
{
    protected $signature = 'kennel:owner {name} {email}';

    protected $description = 'Create (or reset the password of) an admin login. Registration is disabled on the site.';

    public function handle(): int
    {
        $password = (string) $this->secret('Password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->error('Password too short.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->argument('name'), 'password' => $password, 'email_verified_at' => now()],
        );

        $this->info("Login ready for {$user->email}.");

        return self::SUCCESS;
    }
}
