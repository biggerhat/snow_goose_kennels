<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LitterStatus;
use App\Enums\PuppyStatus;
use App\Enums\WaitlistStatus;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Puppy;
use App\Models\WaitlistEntry;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $litter = fn ($q) => $q->with(['sire:id,name', 'dam:id,name'])->withCount('puppies');

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'dogs' => Dog::count(),
                'contacts' => Contact::count(),
                'waiting' => WaitlistEntry::whereIn('status', [WaitlistStatus::New, WaitlistStatus::Waiting, WaitlistStatus::Contacted])->count(),
                'available' => Puppy::where('status', PuppyStatus::Available)->count(),
            ],
            'upcoming' => Litter::query()
                ->whereIn('status', [LitterStatus::Planned, LitterStatus::Expected])
                ->tap($litter)->orderBy('due_on')->get()->append('title'),
            'onGround' => Litter::query()
                ->where('status', LitterStatus::OnGround)
                ->tap($litter)->orderBy('go_home_on')->get()->append('title'),
            'needsFollowUp' => WaitlistEntry::query()
                ->with(['contact:id,name,phone,email', 'litter.sire:id,name', 'litter.dam:id,name'])
                ->whereIn('status', [WaitlistStatus::New, WaitlistStatus::Waiting])->oldest()->limit(6)->get()
                ->each(fn ($e) => $e->litter?->append('title')),
            'soon' => Litter::query()
                ->with(['sire:id,name', 'dam:id,name'])
                ->whereIn('status', [LitterStatus::Planned, LitterStatus::Expected, LitterStatus::OnGround])
                ->get()
                ->flatMap(fn (Litter $l) => array_filter([
                    $l->due_on && $l->due_on->between(today(), today()->addDays(21)) && $l->status !== LitterStatus::OnGround
                        ? ['kind' => 'Due', 'date' => $l->due_on->toDateString(), 'title' => $l->title, 'slug' => $l->slug] : null,
                    $l->go_home_on && $l->go_home_on->between(today(), today()->addDays(21))
                        ? ['kind' => 'Pups go home', 'date' => $l->go_home_on->toDateString(), 'title' => $l->title, 'slug' => $l->slug] : null,
                ]))
                ->sortBy('date')->values(),
            'recentNotes' => ContactNote::with('contact:id,name')->latest('noted_at')->limit(5)->get(),
        ]);
    }
}
