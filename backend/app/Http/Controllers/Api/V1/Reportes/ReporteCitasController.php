<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteCitasRequest;
use App\Queries\Reportes\ReporteCitasQuery;
use App\Services\Reportes\ReporteAccess;
use App\Services\Reportes\ReportePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReporteCitasController extends Controller
{
    public function index(ReporteCitasRequest $request, ReporteAccess $access, ReporteCitasQuery $query): JsonResponse
    {
        $access->authorize($request->user(), 'reportes.ver');

        return response()->json(['data' => $query->build($request->range(), $request->validated())]);
    }

    public function pdf(ReporteCitasRequest $request, ReporteAccess $access, ReporteCitasQuery $query, ReportePdfService $pdf): Response
    {
        $access->authorize($request->user(), 'reportes.exportar');
        $data = $query->build($request->range(), $request->validated());

        return $pdf->download('reports.citas', $data, "reporte-citas-{$data['filtros']['desde']}-{$data['filtros']['hasta']}.pdf");
    }
}
