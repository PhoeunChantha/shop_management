<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Concerns;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamsReportCsv
{
    /**
     * Return either a CSV or a PDF for the given rows based on ?format=pdf.
     * The first row is treated as the header line.
     *
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    protected function streamExport(array $rows, string $title, string $filename, string $format = 'csv'): Response
    {
        return $format === 'pdf'
            ? $this->streamPdf($rows, $title, $filename)
            : $this->streamCsv($rows, $filename);
    }

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    protected function streamPdf(array $rows, string $title, string $filename): Response
    {
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'title' => $title,
            'generatedAt' => now()->format('M d, Y H:i'),
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * @param  array<int, array<int, string|int|float>>  $rows
     */
    protected function streamCsv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename.'-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
