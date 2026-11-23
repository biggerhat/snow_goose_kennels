<?php

namespace App\Support;

use App\Models\Dog;
use App\Models\Litter;
use App\Models\Puppy;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Where the kennel stands against AKC's Bred with H.E.A.R.T. and Breeder of Merit programs.
 * Only a checklist: AKC decides who qualifies, and its rules change, so each line points to akc.org.
 * What the app cannot know (years in AKC events, club membership, courses) she confirms herself.
 */
class AkcPrograms
{
    public const KEYS = ['akc_years_in_events', 'akc_club_member', 'akc_good_standing', 'akc_education_done_on'];

    /** @return array<string, mixed> */
    public static function report(): array
    {
        $breeding = Dog::where('is_ours', true)->whereNull('left_on')->whereIn('role', ['stud', 'dam'])->withCount('healthTests')->get();
        $untested = $breeding->where('health_tests_count', 0);

        $recentAkcLitters = Litter::withTrashed()->where(fn ($q) => $q->whereNotNull('akc_litter_number')->orWhereNotNull('akc_applied_on'))
            ->where('born_on', '>', today()->subYears(5))->count();

        $gone = Puppy::where('status', 'home')->whereHas('litter', fn ($q) => $q->where('born_on', '>', today()->subYears(5)))->get();
        $registered = $gone->filter(fn (Puppy $p) => filled($p->akc_number))->count();

        $years = (int) Setting::get('akc_years_in_events', '0');
        $club = Setting::get('akc_club_member') === '1';
        $standing = Setting::get('akc_good_standing') === '1';
        $education = Setting::get('akc_education_done_on');
        $educationOk = $education && today()->diffInDays(Carbon::parse($education), true) <= 365;

        $healthItem = [
            'key' => 'health', 'title' => 'Health tests on your breeding dogs',
            'status' => $breeding->isEmpty() ? 'todo' : ($untested->isEmpty() ? 'ok' : 'todo'),
            'detail' => $breeding->isEmpty() ? 'No breeding dogs are entered yet.' : ($untested->isEmpty()
                ? 'Every breeding dog has at least one test on file. Check akc.org for the full list your breed’s parent club recommends.'
                : $untested->pluck('name')->join(', ').' ha'.($untested->count() === 1 ? 's' : 've').' no health tests entered yet. The Labrador Retriever Club lists the tests it recommends; check akc.org for the list.'),
            'href' => $untested->count() === 1 ? route('admin.dogs.edit', $untested->first()) : route('admin.dogs.index'),
        ];

        return [
            'heart' => [
                'title' => 'AKC Bred with H.E.A.R.T.',
                'url' => 'https://www.akc.org/breeder-programs/akc-bred-with-heart-program/requirements/',
                'items' => [
                    ['key' => 'litter', 'title' => 'At least one AKC litter in the last 5 years', 'status' => $recentAkcLitters ? 'ok' : 'todo', 'detail' => $recentAkcLitters ? "{$recentAkcLitters} litter".($recentAkcLitters === 1 ? '' : 's').' with an AKC application on file.' : 'Send in an AKC litter application first.', 'href' => null],
                    $healthItem,
                    ['key' => 'education', 'title' => 'Breeder education course in the last year', 'status' => $educationOk ? 'ok' : 'todo', 'detail' => $education ? 'You finished one on '.Carbon::parse($education)->format('M j, Y').'.' : 'Tell us the date below when you finish an AKC breeder course.', 'href' => null],
                    ['key' => 'standing', 'title' => 'In good standing with AKC', 'status' => $standing ? 'ok' : 'todo', 'detail' => 'You confirm this below.', 'href' => null],
                ],
            ],
            'merit' => [
                'title' => 'AKC Breeder of Merit',
                'url' => 'https://www.akc.org/breeder-programs/akc-breeder-of-merit-program/breeder-of-merit-requirements/',
                'items' => [
                    ['key' => 'years', 'title' => 'At least 5 years in AKC events', 'status' => $years >= 5 ? 'ok' : 'todo', 'detail' => $years ? "You said {$years} year".($years === 1 ? '' : 's').'.' : 'Tell us how long below.', 'href' => null],
                    ['key' => 'club', 'title' => 'Member of an AKC breed club', 'status' => $club ? 'ok' : 'todo', 'detail' => 'You confirm this below.', 'href' => null],
                    ['key' => 'register', 'title' => 'Every puppy registered individually', 'status' => $gone->isEmpty() ? 'todo' : ($registered === $gone->count() ? 'ok' : 'todo'), 'detail' => $gone->isEmpty() ? 'No puppies have gone home in the last 5 years.' : "{$registered} of {$gone->count()} puppies that went home have an AKC number recorded.", 'href' => null],
                    $healthItem,
                    ['key' => 'titles', 'title' => 'Dogs you bred that earned AKC titles', 'status' => 'info', 'detail' => 'Titles are entered on each dog. You have '.Dog::whereNotNull('titles')->where('titles', '!=', '')->count().' dogs with titles listed. AKC counts titles on dogs you bred.', 'href' => null],
                ],
            ],
            'answers' => ['years' => $years ?: null, 'club' => $club, 'standing' => $standing, 'education' => $education],
        ];
    }
}
