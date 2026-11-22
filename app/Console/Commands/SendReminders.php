<?php

namespace App\Console\Commands;

use App\Enums\LitterStatus;
use App\Enums\WaitlistStatus;
use App\Mail\DailyReminders;
use App\Models\Litter;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReminders extends Command
{
    protected $signature = 'kennel:reminders';

    protected $description = 'Email the owner about litters due or going home this week and applications left unanswered.';

    public function handle(): int
    {
        $litters = Litter::with(['sire:id,name', 'dam:id,name'])->get();

        $due = $litters
            ->filter(fn (Litter $l) => in_array($l->status, [LitterStatus::Planned, LitterStatus::Expected], true)
                && $l->due_on?->between(today(), today()->addDays(7)))
            ->sortBy('due_on')->values();

        $goingHome = $litters
            ->filter(fn (Litter $l) => $l->status !== LitterStatus::AllHome
                && $l->go_home_on?->between(today(), today()->addDays(7)))
            ->sortBy('go_home_on')->values();

        $unanswered = WaitlistEntry::with('contact')
            ->where('status', WaitlistStatus::New)
            ->where('created_at', '<=', now()->subDays(2))
            ->oldest()->get();

        if ($due->isEmpty() && $goingHome->isEmpty() && $unanswered->isEmpty()) {
            $this->info('Nothing to remind about today.');

            return self::SUCCESS;
        }

        $to = User::pluck('email')->all();

        if ($to === []) {
            $this->warn('No admin logins to email.');

            return self::SUCCESS;
        }

        Mail::to($to)->send(new DailyReminders($due, $goingHome, $unanswered));
        $this->info('Reminder email sent to '.count($to).' login(s).');

        return self::SUCCESS;
    }
}
