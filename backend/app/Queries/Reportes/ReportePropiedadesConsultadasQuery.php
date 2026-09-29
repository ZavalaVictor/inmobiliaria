<?php

namespace App\Queries\Reportes;

use App\Models\Inmueble;
use App\Services\Reportes\ReportDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ReportePropiedadesConsultadasQuery
{
    /** @param array<string, mixed> $filters */
    public function build(ReportDateRange $range, array $filters): array
    {
        $generatedAt = CarbonImmutable::now(config('app.timezone'));
        $base = $this->base($range, $filters, $generatedAt);
        $top = (clone $base)->orderByDesc('visualizaciones')->orderBy('inmuebles.id')->limit(10)->get()->map(fn ($row): array => $this->mapProperty($row))->all();
        $bottom = (clone $base)->orderBy('visualizaciones')->orderBy('inmuebles.id')->limit(10)->get()->map(fn ($row): array => $this->mapProperty($row))->all();
        $rows = (clone $base)->get();

        $byCategory = (clone $base)->select('categorias.id as categoria_id', 'categorias.nombre as categoria', DB::raw('AVG(visualizaciones) AS promedio_visualizaciones'))->groupBy('categorias.id', 'categorias.nombre')->orderBy('categorias.id')->get()->map(fn ($row): array => ['categoria_id' => (int) $row->categoria_id, 'categoria' => $row->categoria, 'promedio_visualizaciones' => round((float) $row->promedio_visualizaciones, 2)])->all();
        $byZone = (clone $base)->select('inmuebles.municipio as zona', DB::raw('AVG(visualizaciones) AS promedio_visualizaciones'))->groupBy('inmuebles.municipio')->orderBy('inmuebles.municipio')->get()->map(fn ($row): array => ['zona' => $row->zona, 'promedio_visualizaciones' => round((float) $row->promedio_visualizaciones, 2)])->all();
        $bands = (clone $base)->select([])->selectRaw("CASE WHEN {$this->daysExpression($generatedAt)} <= 30 THEN '0-30' WHEN {$this->daysExpression($generatedAt)} <= 90 THEN '31-90' WHEN {$this->daysExpression($generatedAt)} <= 180 THEN '91-180' ELSE '181+' END AS rango, COUNT(*) AS total_inmuebles, COALESCE(SUM(COALESCE(report_views.visualizaciones, 0)), 0) AS total_visualizaciones, AVG(COALESCE(report_views.visualizaciones, 0)) AS promedio_visualizaciones")->groupByRaw("CASE WHEN {$this->daysExpression($generatedAt)} <= 30 THEN '0-30' WHEN {$this->daysExpression($generatedAt)} <= 90 THEN '31-90' WHEN {$this->daysExpression($generatedAt)} <= 180 THEN '91-180' ELSE '181+' END")->get()->keyBy('rango');

        return [
            'reporte' => 'propiedades-consultadas',
            'generado_en' => $generatedAt->toISOString(),
            'filtros' => ['desde' => $range->desde, 'hasta' => $range->hasta, ...array_intersect_key($filters, array_flip(['categoria_id', 'zona', 'precio_min', 'precio_max']))],
            'resumen' => ['total_visualizaciones' => (int) $rows->sum('visualizaciones')],
            'graficas' => ['promedio_visualizaciones_por_categoria' => $byCategory, 'promedio_visualizaciones_por_zona' => $byZone],
            'tablas' => [
                'top_consultadas' => $top,
                'bottom_consultadas' => $bottom,
                'antiguedad_vs_consultas' => [
                    'por_inmueble' => $rows->map(fn ($row): array => ['inmueble_id' => (int) $row->id, 'dias_publicado' => max(0, (int) $row->dias_publicado), 'visualizaciones' => (int) $row->visualizaciones])->all(),
                    'por_banda' => array_map(fn (string $key): array => ['rango' => $key, 'total_inmuebles' => (int) ($bands->get($key)?->total_inmuebles ?? 0), 'total_visualizaciones' => (int) ($bands->get($key)?->total_visualizaciones ?? 0), 'promedio_visualizaciones' => round((float) ($bands->get($key)?->promedio_visualizaciones ?? 0), 2)], ['0-30', '31-90', '91-180', '181+']),
                ],
            ],
        ];
    }

    private function base(ReportDateRange $range, array $filters, CarbonImmutable $generatedAt): Builder
    {
        $views = DB::table('visualizaciones_inmuebles')
            ->select('inmueble_id', DB::raw('COUNT(*) AS visualizaciones'))
            ->whereBetween('fecha_visualizacion', [$range->inicio, $range->fin])
            ->groupBy('inmueble_id');
        $days = $this->daysExpression($generatedAt);
        $price = "CASE WHEN inmuebles.tipo_operacion = 'venta' THEN inmuebles.precio_venta ELSE inmuebles.renta_mensual END";
        $query = Inmueble::query()->from('inmuebles')->join('categorias', 'categorias.id', '=', 'inmuebles.categoria_id')->leftJoinSub($views, 'report_views', fn ($join) => $join->on('report_views.inmueble_id', '=', 'inmuebles.id'))->whereNull('inmuebles.deleted_at')->select('inmuebles.id', 'inmuebles.codigo', 'inmuebles.titulo', 'categorias.id as categoria_id', 'categorias.nombre as categoria', 'inmuebles.municipio as zona', 'inmuebles.tipo_operacion', 'inmuebles.estado_disponibilidad', DB::raw("{$price} AS precio"), DB::raw('COALESCE(report_views.visualizaciones, 0) AS visualizaciones'), DB::raw("{$days} AS dias_publicado"));

        if (isset($filters['categoria_id'])) {
            $query->where('inmuebles.categoria_id', $filters['categoria_id']);
        }
        if (isset($filters['zona'])) {
            $query->where('inmuebles.municipio', $filters['zona']);
        }
        if (isset($filters['precio_min'])) {
            $query->whereRaw("{$price} >= ?", [$filters['precio_min']]);
        }
        if (isset($filters['precio_max'])) {
            $query->whereRaw("{$price} <= ?", [$filters['precio_max']]);
        }

        return $query;
    }

    private function daysExpression(CarbonImmutable $generatedAt): string
    {
        return "GREATEST(0, DATEDIFF('{$generatedAt->toDateString()}', COALESCE(inmuebles.fecha_publicacion, inmuebles.created_at)))";
    }

    private function mapProperty(object $row): array
    {
        return ['inmueble_id' => (int) $row->id, 'codigo' => $row->codigo, 'titulo' => $row->titulo, 'categoria' => $row->categoria, 'zona' => $row->zona, 'tipo_operacion' => $row->tipo_operacion, 'precio' => (float) $row->precio, 'estado' => $row->estado_disponibilidad, 'visualizaciones' => (int) $row->visualizaciones, 'dias_publicado' => max(0, (int) $row->dias_publicado)];
    }
}
