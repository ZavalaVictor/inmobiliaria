<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteComparativoRequest;
use App\Queries\Reportes\ReporteComparativoQuery;
use App\Services\Reportes\ReporteAccess;
use App\Services\Reportes\ReportePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReporteComparativoController extends Controller
{
    public function index(ReporteComparativoRequest $request, ReporteAccess $access, ReporteComparativoQuery $query): JsonResponse
    {
        $access->authorize($request->user(), 'reportes.ver');

        return response()->json(['data' => $query->build($request->ranges())]);
    }

    public function pdf(ReporteComparativoRequest $request, ReporteAccess $access, ReporteComparativoQuery $query, ReportePdfService $pdf): Response
    {
        $access->authorize($request->user(), 'reportes.exportar');
        $data = $query->build($request->ranges());

        return $pdf->download('reports.comparativo-periodos', $data, 'reporte-comparativo-periodos.pdf');
    }
}
