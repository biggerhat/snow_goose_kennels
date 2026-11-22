<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\Litter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiaryEntryController extends Controller
{
    public function store(Request $request, Litter $litter): RedirectResponse
    {
        $litter->diary()->create($request->validate([
            'posted_on' => ['required', 'date', 'before_or_equal:today'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:3000'],
            'is_public' => ['boolean'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Update posted.']);

        return back();
    }

    public function destroy(DiaryEntry $diaryEntry): RedirectResponse
    {
        $diaryEntry->delete();

        return back();
    }
}
