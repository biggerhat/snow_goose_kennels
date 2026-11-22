<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlbumController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $album = Album::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Album “{$album->name}” created."]);

        return to_route('admin.photos.index', ['album' => $album->id]);
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        $album->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    /** Photos are kept; they just become unsorted. */
    public function destroy(Album $album): RedirectResponse
    {
        $album->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Album removed. Its photos are still in your library.']);

        return to_route('admin.photos.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
