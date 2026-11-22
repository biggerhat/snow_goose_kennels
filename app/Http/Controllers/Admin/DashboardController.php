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
use App\Models\Photo;
use App\Models\Puppy;
use App\Models\Setting;
use App\Models\WaitlistEntry;
use App\Support\SiteContent;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $litter = fn ($q) => $q->with(['sire:id,name', 'dam:id,name'])->withCount('puppies');

        return Inertia::render('admin/Dashboard', [
            'setup' => $this->setup(),
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

    /**
     * A short first-run checklist so a brand new kennel knows where to start.
     *
     * @return list<array{key: string, title: string, hint: string, done: bool, href: string}>
     */
    private function setup(): array
    {
        $contact = Setting::contact();

        return [
            ['key' => 'contact', 'title' => 'Add your phone, email and town', 'hint' => 'So visitors can reach you.', 'done' => filled($contact['phone'] ?? null) || filled($contact['email'] ?? null), 'href' => route('admin.site.edit')],
            ['key' => 'dogs', 'title' => 'Add your dogs', 'hint' => 'Mothers, fathers, and retired dogs.', 'done' => Dog::exists(), 'href' => route('admin.dogs.create')],
            ['key' => 'litter', 'title' => 'Add a litter', 'hint' => 'One that is planned, expected or on the ground.', 'done' => Litter::exists(), 'href' => route('admin.litters.create')],
            ['key' => 'photos', 'title' => 'Put a photo on the website', 'hint' => 'Add one to a dog or litter, or in the Photos library.', 'done' => Photo::where('is_public', true)->exists(), 'href' => route('admin.photos.index')],
            ['key' => 'text', 'title' => 'Write how it works (optional)', 'hint' => 'Deposits, go-home age and so on, in your own words.', 'done' => SiteContent::hasAny(), 'href' => route('admin.site.edit')],
        ];
    }
}
