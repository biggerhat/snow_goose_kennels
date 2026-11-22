<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\KennelProfile;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    private const INLINE = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:25600'],
            'documentable_type' => ['required', Rule::in(['dog', 'litter', 'puppy', 'contact', 'kennel'])],
            'documentable_id' => ['required', 'integer'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'title' => ['nullable', 'string', 'max:200'],
            'issued_on' => ['nullable', 'date', 'before_or_equal:today'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'file.required' => 'Choose a file to add.',
            'file.mimes' => 'That kind of file is not allowed. Use a PDF, a photo (JPG or PNG) or a Word or Excel file.',
            'file.uploaded' => 'The file could not be uploaded. It may be too big for this server.',
        ]);

        $owner = $data['documentable_type'] === 'kennel'
            ? KennelProfile::instance()
            : Relation::getMorphedModel($data['documentable_type'])::findOrFail($data['documentable_id']);
        $file = $data['file'];
        $name = Str::uuid()->toString().'.'.($file->guessExtension() ?: 'bin');
        $path = $file->storeAs('documents/'.now()->format('Y/m'), $name, 'local');

        $owner->documents()->create([
            'category' => $data['category'],
            'title' => (($data['title'] ?? null) ?: Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 190, '')),
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document added.']);

        return back();
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $document->update($request->validate([
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'title' => ['required', 'string', 'max:200'],
            'issued_on' => ['nullable', 'date', 'before_or_equal:today'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    /** Only a logged-in owner reaches this route; files are never public. */
    public function show(Document $document): Response
    {
        $inline = in_array($document->mime, self::INLINE, true);
        $filename = Str::ascii($document->original_name) ?: 'document';

        return Storage::disk('local')->response($document->path, $filename, [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ], $inline ? 'inline' : 'attachment');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Document removed.']);

        return back();
    }
}
