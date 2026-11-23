<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Litter;
use App\Support\AkcLitterApplication;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AkcApplicationController extends Controller
{
    public function show(Litter $litter): Response
    {
        return Inertia::render('admin/akc/LitterApplication', [
            'litter' => ['id' => $litter->id, 'slug' => $litter->slug, 'title' => $litter->title],
            'application' => AkcLitterApplication::build($litter),
            'onlineUrl' => AkcLitterApplication::ONLINE_URL,
        ]);
    }

    /**
     * AKC's current blank form, fetched from AKC when needed and kept for a month. It is not stored in the
     * project: AKC revises it, and it is AKC's document.
     */
    public function form(): HttpResponse
    {
        $disk = Storage::disk('local');
        $path = 'akc/litter-application.pdf';
        $fresh = $disk->exists($path) && $disk->lastModified($path) > now()->subDays(30)->timestamp;

        if (! $fresh) {
            try {
                $body = Http::timeout(20)->accept('application/pdf')->get(AkcLitterApplication::FORM_URL)->throw()->body();

                if (str_starts_with($body, '%PDF') && strlen($body) < 10_000_000) {
                    $disk->put($path, $body);
                }
            } catch (ConnectionException|RequestException) {
                // fall through to a stale copy, or a clear error
            }
        }

        abort_unless($disk->exists($path), 502, 'AKC’s website did not answer. Try again in a minute, or use the worksheet.');

        return $disk->response($path, 'akc-litter-application.pdf', ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
