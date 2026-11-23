<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\NewStory;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/** Families can send in their own story. It stays hidden until the owner approves it. */
class StorySubmissionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('public/ShareStory', [
            'meta' => SiteController::meta('Share your story', 'Tell us how your Snow Goose puppy is doing.'),
            'submitted' => $request->session()->get('story_submitted', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real people never see or fill this field.
        if (filled($request->input('website'))) {
            return to_route('site.story')->with('story_submitted', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'puppy_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:200'],
            'body' => ['required', 'string', 'min:15', 'max:2000'],
            'permission' => ['accepted'],
        ], [
            'body.required' => 'Please write a few words about your puppy.',
            'body.min' => 'Please write a little more.',
            'permission.accepted' => 'Tick the box to say we may show your story on the website.',
        ]);

        $story = Testimonial::create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'puppy_name' => $data['puppy_name'] ?? null,
            'email' => $data['email'] ?? null,
            'body' => $data['body'],
            'is_public' => false,
            'needs_review' => true,
        ]);

        try {
            Mail::to(User::pluck('email')->all())->send(new NewStory($story));
        } catch (\Throwable $e) {
            report($e); // never lose a story because email failed
        }

        return to_route('site.story')->with('story_submitted', true);
    }
}
