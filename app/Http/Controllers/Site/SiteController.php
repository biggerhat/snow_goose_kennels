<?php

namespace App\Http\Controllers\Site;

use App\Enums\DogRole;
use App\Enums\LitterStatus;
use App\Http\Controllers\Controller;
use App\Models\Dog;
use App\Models\Litter;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Pedigree;
use App\Support\SiteContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public pages. Everything returned here is explicitly whitelisted so
 * private fields (contacts, buyers, notes, registrations) never leak.
 */
class SiteController extends Controller
{
    public function home(): Response
    {
        $litters = $this->publicLitters();

        return Inertia::render('public/Home', [
            'meta' => self::meta(null, 'Family-raised black, chocolate and yellow Labrador retrievers. Upcoming litters, dates, prices and health testing.'),
            'next' => $litters->first(fn ($l) => in_array($l['status'], ['planned', 'expected'])),
            'onGround' => $litters->where('status', 'on_ground')->values(),
            'dogs' => $this->publicDogs()->whereIn('role', ['stud', 'dam'])->take(4)->values(),
            'gallery' => $this->galleryPhotos()->take(8)->values(),
            'quotes' => Testimonial::where('is_public', true)->orderBy('sort_order')->latest('id')->limit(3)->get(['id', 'name', 'location', 'puppy_name', 'body']),
            'contact' => Setting::contact(),
        ]);
    }

    public function litters(): Response
    {
        $litters = $this->publicLitters();

        return Inertia::render('public/Litters', [
            'meta' => self::meta('Litters', 'Upcoming, current and past Labrador litters with due dates, prices, deposits and parents.'),
            'upcoming' => $litters->whereIn('status', ['planned', 'expected'])->values(),
            'onGround' => $litters->where('status', 'on_ground')->values(),
            'past' => $litters->where('status', 'all_home')->values(),
            'contact' => Setting::contact(),
        ]);
    }

    public function dogs(): Response
    {
        return Inertia::render('public/Dogs', ['dogs' => $this->publicDogs(), 'meta' => self::meta('Our dogs', 'The studs and dams behind every litter, with their health clearances.')]);
    }

    public function dog(Dog $dog): Response
    {
        abort_unless($dog->is_public, 404);

        return Inertia::render('public/DogShow', [
            'dog' => $this->dogData($dog->load(['photos' => fn ($q) => $q->where('is_public', true), 'healthTests' => fn ($q) => $q->where('is_public', true)])),
            'pedigree' => ($dog->sire_id || $dog->dam_id) ? Pedigree::build($dog, 3, public: true) : null,
            'meta' => self::meta($dog->name, self::dogDescription($dog), $dog->photos->where('is_public', true)->first()?->url),
        ]);
    }

    public function gallery(): Response
    {
        return Inertia::render('public/Gallery', ['photos' => $this->galleryPhotos(), 'meta' => self::meta('Gallery', 'Photos of our Labrador retrievers and puppies.')]);
    }

    public function privacy(): Response
    {
        return Inertia::render('public/Privacy', [
            'meta' => self::meta('Privacy', 'What information we collect through this website, how we use it, and who sees it.'),
            'contact' => Setting::contact(),
        ]);
    }

    public function sitemap()
    {
        $urls = collect(['/', '/litters', '/our-dogs', '/gallery', '/apply', '/contact', '/privacy'])
            ->map(fn ($p) => url($p))
            ->merge(Dog::where('is_public', true)->pluck('slug')->map(fn ($slug) => url("/our-dogs/$slug")));

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$urls->map(fn ($u) => '<url><loc>'.e($u).'</loc></url>')->implode('')
            .'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function contact(): Response
    {
        return Inertia::render('public/Contact', [
            'meta' => self::meta('Contact', 'Call, email or apply for a puppy. How reserving a puppy works, and kind words from families who took one home.'),
            'contact' => Setting::contact(),
            'sections' => SiteContent::filled(),
            'testimonials' => Testimonial::where('is_public', true)->orderBy('sort_order')->latest('id')
                ->get(['id', 'name', 'location', 'puppy_name', 'body']),
        ]);
    }

    /**
     * What search engines and link previews should show for a page. The browser sets the same things
     * with JavaScript, but crawlers and chat apps read them from the first response.
     *
     * @return array{title: string|null, description: string|null, image: string|null}
     */
    public static function meta(?string $title, ?string $description = null, ?string $image = null): array
    {
        return ['title' => $title, 'description' => $description, 'image' => $image];
    }

    private static function dogDescription(Dog $dog): string
    {
        $color = $dog->color ? strtolower($dog->color->label()).' ' : '';
        $base = "{$dog->name}".($dog->registered_name ? " ({$dog->registered_name})" : '').", a {$color}Labrador retriever at ".config('app.name').'.';

        return Str::limit(trim($base.' '.($dog->bio ?? '')), 200);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function publicLitters(): Collection
    {
        return Litter::query()
            ->where('is_public', true)
            ->with([
                'sire.photos' => fn ($q) => $q->where('is_public', true),
                'dam.photos' => fn ($q) => $q->where('is_public', true),
                'puppies.photos',
                'diary' => fn ($q) => $q->where('is_public', true)->limit(5),
                'photos' => fn ($q) => $q->where('is_public', true),
            ])
            ->get()
            ->sortBy(fn (Litter $l) => [array_search($l->status, [LitterStatus::Expected, LitterStatus::Planned, LitterStatus::OnGround, LitterStatus::AllHome]), $l->status === LitterStatus::AllHome ? -($l->born_on?->timestamp ?? 0) : ($l->due_on?->timestamp ?? PHP_INT_MAX)])
            ->values()
            ->map(fn (Litter $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'description' => $l->description,
                'status' => $l->status->value,
                'bred_on' => $l->bred_on?->format('Y-m-d'),
                'due_on' => $l->due_on?->format('Y-m-d'),
                'born_on' => $l->born_on?->format('Y-m-d'),
                'go_home_on' => $l->go_home_on?->format('Y-m-d'),
                'updated_on' => $l->updated_at?->format('Y-m-d'),
                'price' => $l->price,
                'deposit' => $l->deposit,
                'expected_count' => $l->expected_count,
                'sire' => $l->sire ? $this->parent($l->sire) : null,
                'dam' => $l->dam ? $this->parent($l->dam) : null,
                'puppies' => $l->puppies->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'collar' => $p->collar,
                    'sex' => $p->sex->value,
                    'color' => $p->color?->value,
                    'status' => $p->status->value,
                    'photo' => ($ph = $p->photos->firstWhere('is_public', true)) ? $this->photo($ph) : null,
                ])->values(),
                'diary' => $l->diary->map(fn ($d) => ['id' => $d->id, 'posted_on' => $d->posted_on->format('Y-m-d'), 'title' => $d->title, 'body' => $d->body])->values(),
                'photos' => $l->photos->map(fn ($p) => $this->photo($p))->values(),
            ]);
    }

    /** @return array<string, mixed> */
    private function parent(Dog $dog): array
    {
        return [
            'name' => $dog->name,
            'slug' => $dog->is_public ? $dog->slug : null,
            'color' => $dog->color?->value,
            'photo' => $dog->photos->first() ? $this->photo($dog->photos->first()) : null,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function publicDogs(): Collection
    {
        return Dog::query()
            ->where('is_public', true)
            ->with(['photos' => fn ($q) => $q->where('is_public', true)])
            ->get()
            ->sortBy(fn (Dog $d) => [array_search($d->role, [DogRole::Stud, DogRole::Dam, DogRole::Other, DogRole::Retired]), $d->sort_order, $d->name])
            ->values()
            ->map(fn (Dog $d) => $this->dogData($d));
    }

    /** @return array<string, mixed> */
    private function dogData(Dog $dog): array
    {
        return [
            'slug' => $dog->slug,
            'name' => $dog->name,
            'registered_name' => $dog->registered_name,
            'titles' => $dog->titles,
            'sex' => $dog->sex->value,
            'role' => $dog->role->value,
            'color' => $dog->color?->value,
            'birth_date' => $dog->birth_date?->format('Y-m-d'),
            'age_label' => $dog->age_label,
            'bio' => $dog->bio,
            'photos' => $dog->photos->map(fn ($p) => $this->photo($p))->values(),
            'health_tests' => $dog->relationLoaded('healthTests')
                ? $dog->healthTests->map(fn ($t) => ['name' => $t->name, 'result' => $t->result, 'tested_on' => $t->tested_on?->format('Y-m-d'), 'certificate_number' => $t->certificate_number, 'verification_url' => $t->verification_url])->values()
                : [],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function galleryPhotos(): Collection
    {
        return Photo::query()->where('is_public', true)->where('in_gallery', true)
            ->orderBy('sort_order')->latest('id')->get()
            ->map(fn (Photo $p) => $this->photo($p))->values();
    }

    /** @return array<string, mixed> */
    private function photo(Photo $p): array
    {
        return ['id' => $p->id, 'url' => $p->url, 'thumb_url' => $p->thumb_url, 'caption' => $p->caption, 'credit' => $p->credit];
    }
}
