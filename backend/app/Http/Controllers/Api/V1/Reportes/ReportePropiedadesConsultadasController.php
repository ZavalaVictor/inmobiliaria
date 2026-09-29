<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportes\ReportePropiedadesConsultadasRequest;
use App\Queries\Reportes\ReportePropiedadesConsultadasQuery;
use App\Services\Reportes\ReporteAccess;
use App\Services\Reportes\ReportePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportePropiedadesConsultadasController extends Controller
{
    public function index(ReportePropiedadesConsultadasRequest $request, ReporteAccess $access, ReportePropiedadesConsultadasQuery $query): JsonResponse
    {
        $access->authorize($request->user(), 'reportes.ver');

        return response()->json(['data' => $query->build($request->range(), $request->validated())]);
    }

    public function pdf(ReportePropiedadesConsultadasRequest $request, ReporteAccess $access, ReportePropiedadesConsultadasQuery $query, ReportePdfService $pdf): Response
    {
        $access->authorize($request->user(), 'reportes.exportar');
        $data = $query->build($request->range(), $request->validated());

        return $pdf->download('reports.propiedades-consultadas', $data, "reporte-propiedades-consultadas-{$data['filtros']['desde']}-{$data['filtros']['hasta']}.pdf");
    }
}
