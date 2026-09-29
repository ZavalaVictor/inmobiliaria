<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReporteInmueblesRequest;
use App\Queries\Reportes\ReporteInmueblesQuery;
use App\Services\Reportes\ReporteAccess;
use App\Services\Reportes\ReportePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReporteInmueblesController extends Controller
{
    public function index(ReporteInmueblesRequest $request, ReporteAccess $access, ReporteInmueblesQuery $query): JsonResponse
    {
        $access->authorize($request->user(), 'reportes.ver');

        return response()->json(['data' => $query->build($request->range(), $request->validated())]);
    }

    public function pdf(ReporteInmueblesRequest $request, ReporteAccess $access, ReporteInmueblesQuery $query, ReportePdfService $pdf): Response
    {
        $access->authorize($request->user(), 'reportes.exportar');
        $data = $query->build($request->range(), $request->validated());

        return $pdf->download('reports.inmuebles', $data, "reporte-inmuebles-{$data['filtros']['desde']}-{$data['filtros']['hasta']}.pdf");
    }
}
