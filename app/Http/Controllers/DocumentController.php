<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->is_enabled && $document->isViewableBy($request->user()), 403);

        $disk = Storage::disk(Document::DISK);
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, $document->name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
