<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Photo;
use App\Services\ImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PhotoController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'album' => ['nullable', 'string', 'max:10'],
            'show' => ['nullable', Rule::in(['public', 'private'])],
            'subject' => ['nullable', Rule::in(['dog', 'litter', 'puppy', 'none'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $photos = Photo::query()
            ->with('photoable')
            ->when(($filters['album'] ?? null) === 'none', fn ($q) => $q->whereNull('album_id'))
            ->when(ctype_digit($filters['album'] ?? ''), fn ($q) => $q->where('album_id', (int) $filters['album']))
            ->when(($filters['show'] ?? null) === 'public', fn ($q) => $q->where('is_public', true))
            ->when(($filters['show'] ?? null) === 'private', fn ($q) => $q->where('is_public', false))
            ->when(($filters['subject'] ?? null) === 'none', fn ($q) => $q->whereNull('photoable_type'))
            ->when(in_array($filters['subject'] ?? null, ['dog', 'litter', 'puppy'], true), fn ($q) => $q->where('photoable_type', $filters['subject']))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('caption', 'like', "%{$term}%")->orWhere('credit', 'like', "%{$term}%")))
            ->orderByDesc('id')
            ->paginate(48)
            ->withQueryString()
            ->through(function (Photo $p) {
                $subject = $this->subject($p);

                return [...$p->unsetRelation('photoable')->toArray(), 'subject' => $subject];
            });

        return Inertia::render('admin/photos/Index', [
            'photos' => $photos,
            'filters' => [
                'album' => $filters['album'] ?? null,
                'show' => $filters['show'] ?? null,
                'subject' => $filters['subject'] ?? null,
                'q' => $filters['q'] ?? '',
            ],
            'albums' => Album::withCount('photos')->orderBy('sort_order')->orderBy('name')->get(),
            'totals' => [
                'all' => Photo::count(),
                'unsorted' => Photo::whereNull('album_id')->count(),
                'public' => Photo::where('is_public', true)->count(),
                'private' => Photo::where('is_public', false)->count(),
            ],
        ]);
    }

    public function store(Request $request, ImageStore $images): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'max:30'],
            'photos.*' => ['file', 'mimes:'.(ImageStore::supportsHeic() ? 'jpg,jpeg,png,webp,heic,heif' : 'jpg,jpeg,png,webp'), 'max:25600'],
            'photoable_type' => ['nullable', Rule::in(['dog', 'litter', 'puppy'])],
            'photoable_id' => ['nullable', 'required_with:photoable_type', 'integer'],
            'album_id' => ['nullable', 'integer', 'exists:albums,id'],
            'taken_on' => ['nullable', 'date', 'before_or_equal:today'],
            'credit' => ['nullable', 'string', 'max:150'],
            'is_public' => ['boolean'],
            'in_gallery' => ['boolean'],
        ]);

        foreach ($data['photos'] as $file) {
            try {
                $stored = $images->store($file);
            } catch (\Throwable $e) {
                report($e);

                throw ValidationException::withMessages(['photos' => "We could not read {$file->getClientOriginalName()}. Try saving it as a JPG or PNG."]);
            }

            Photo::create([
                ...$stored,
                'photoable_type' => $data['photoable_type'] ?? null,
                'photoable_id' => $data['photoable_id'] ?? null,
                'album_id' => $data['album_id'] ?? null,
                'taken_on' => $data['taken_on'] ?? null,
                'credit' => $data['credit'] ?? null,
                'is_public' => $data['is_public'] ?? true,
                'in_gallery' => $data['in_gallery'] ?? true,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => count($data['photos']).' photo(s) uploaded.']);

        return back();
    }

    /** Move, show/hide or delete several photos at once. */
    public function bulk(Request $request, ImageStore $images): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['move', 'show', 'hide', 'delete'])],
            'album_id' => ['nullable', 'integer', 'exists:albums,id'],
        ]);

        $photos = Photo::whereIn('id', $data['ids']);
        $count = (clone $photos)->count();

        match ($data['action']) {
            'move' => $photos->update(['album_id' => $data['album_id'] ?? null]),
            'show' => $photos->update(['is_public' => true]),
            'hide' => $photos->update(['is_public' => false]),
            'delete' => $photos->get()->each(function (Photo $p) use ($images) {
                $images->delete($p->path, $p->thumb_path);
                $p->delete();
            }),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => "$count photo(s) updated."]);

        return back();
    }

    public function update(Request $request, Photo $photo): RedirectResponse
    {
        $photo->update($request->validate([
            'caption' => ['nullable', 'string', 'max:200'],
            'credit' => ['nullable', 'string', 'max:150'],
            'taken_on' => ['nullable', 'date', 'before_or_equal:today'],
            'album_id' => ['nullable', 'integer', 'exists:albums,id'],
            'is_public' => ['boolean'],
            'in_gallery' => ['boolean'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    public function destroy(Photo $photo, ImageStore $images): RedirectResponse
    {
        $images->delete($photo->path, $photo->thumb_path);
        $photo->delete();

        return back();
    }

    private function subject(Photo $p): ?string
    {
        $o = $p->photoable;

        return $o ? ($o->title ?? $o->name ?? $o->collar ?? null) : null;
    }
}
