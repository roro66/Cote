<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Expense;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ExpensePdfService
{
    /** Lado mayor de las fotos dentro del PDF; las de celular (4000 px) harían un PDF enorme. */
    private const IMAGE_MAX_SIDE = 1400;

    public function download(Expense $expense): Response
    {
        $expense->load(['account.person', 'submittedBy', 'reviewedBy', 'items.categoryObj', 'items.documents']);

        $receipts = [];
        foreach ($expense->items as $index => $item) {
            foreach ($item->documents->where('is_enabled', true) as $document) {
                $receipts[] = [
                    'item' => $index + 1,
                    'name' => $document->name,
                    'image' => str_starts_with((string) $document->mime_type, 'image/') ? $this->documentImage($document) : null,
                ];
            }
        }

        $logoPath = public_path('assets/img/logo01.png');
        $logo = is_file($logoPath) ? $this->toJpeg(file_get_contents($logoPath), 500) : null;

        $pdf = Pdf::loadView('expenses.pdf', [
            'expense' => $expense,
            'receipts' => $receipts,
            'logo' => $logo,
            'generatedAt' => now(),
        ])->setPaper('letter');

        // Numeración al pie: se dibuja después de maquetar, cuando ya se sabe el total de páginas
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 8, [0.4, 0.4, 0.4]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rendicion_' . $expense->expense_number . '.pdf"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @return array{src: string, ratio: float}|null
     */
    private function documentImage(Document $document): ?array
    {
        $disk = Storage::disk(Document::DISK);
        if (! $document->file_path || ! $disk->exists($document->file_path)) {
            return null;
        }

        $angle = 0;
        if ($document->mime_type === 'image/jpeg' && function_exists('exif_read_data')) {
            // Las fotos de celular guardan la rotación en EXIF y GD la ignora
            $exif = @exif_read_data($disk->path($document->file_path));
            $angle = match (is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
        }

        try {
            return $this->toJpeg($disk->get($document->file_path), self::IMAGE_MAX_SIDE, $angle);
        } catch (\Throwable $e) {
            Log::warning('No se pudo incluir un comprobante en el PDF', ['document_id' => $document->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Imagen reducida a $maxSide px, en JPEG sobre fondo blanco, como data URI con su proporción (alto/ancho).
     *
     * @return array{src: string, ratio: float}|null
     */
    private function toJpeg(string $binary, int $maxSide, int $angle = 0): ?array
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }
        if ($angle) {
            $rotated = imagerotate($image, $angle, 0);
            imagedestroy($image);
            $image = $rotated;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        imagedestroy($image);

        $ratio = imagesy($target) / imagesx($target);
        ob_start();
        imagejpeg($target, null, 80);
        imagedestroy($target);

        return ['src' => 'data:image/jpeg;base64,' . base64_encode(ob_get_clean()), 'ratio' => $ratio];
    }
}
