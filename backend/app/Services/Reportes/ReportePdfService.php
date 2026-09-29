<?php

namespace App\Services\Reportes;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ReportePdfService
{
    /** @param array<string, mixed> $data */
    public function download(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, ['data' => $data, 'user' => request()->user()])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }
}
