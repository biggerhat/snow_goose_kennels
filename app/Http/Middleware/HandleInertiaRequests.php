<?php

namespace App\Http\Middleware;

use App\Enums\WaitlistStatus;
use App\Models\Testimonial;
use App\Models\WaitlistEntry;
use App\Support\Attention;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            // The visitor's colour theme, so the server draws the right dog head as selected (and the client agrees on hydration).
            'palette' => in_array($request->cookie('palette'), ['black', 'chocolate', 'yellow'], true) ? $request->cookie('palette') : 'black',
            // Counts for the badges in the owner menu. Cached briefly so every click is not a new count.
            'nav' => fn () => $request->user() ? [
                'applications' => WaitlistEntry::where('status', WaitlistStatus::New)->count(),
                'stories' => Testimonial::where('needs_review', true)->count(),
                'attention' => Cache::remember('nav.attention', 60, fn () => Attention::items(14)->count()),
            ] : null,
            'options' => Options::all(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
