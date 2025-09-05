<?php

namespace App\Services;

use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row as SpoutRow;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Stream an XLSX with headings and rows.
     *
     * @param array<int,string> $headings
     * @param array<int,array<int,mixed>> $rows
     */
    public static function streamXlsx(string $filename, array $headings, array $rows): StreamedResponse
    {
        // Use Laravel's streamDownload to manage headers; write XLSX to php://output
        return response()->streamDownload(function () use ($headings, $rows) {
            $writer = new XlsxWriter();
            // Write directly to output; Laravel handles headers
            $writer->openToFile('php://output');

            // Headings
            $writer->addRow(SpoutRow::fromValues($headings));

            foreach ($rows as $row) {
                $writer->addRow(SpoutRow::fromValues($row));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
