<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /** Tipos que el navegador puede abrir sin ejecutar código; el resto se descarga. */
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function show(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->is_enabled && $document->isViewableBy($request->user()), 403);

        $disk = Storage::disk(Document::DISK);
        abort_unless($disk->exists($document->file_path), 404);

        // Los comprobantes antiguos guardaron el tipo que declaró el navegador al subirlos
        $type = $disk->mimeType($document->file_path) ?: null;
        $inline = in_array($type, self::INLINE_TYPES, true);

        return $disk->response($document->file_path, $document->name, [
            'Content-Type' => $inline ? $type : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }
}
