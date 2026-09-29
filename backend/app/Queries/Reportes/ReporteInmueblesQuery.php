<?php

namespace App\Queries\Reportes;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\EstadoOperacion;
use App\Models\Agente;
use App\Models\Inmueble;
use App\Services\Reportes\ReportDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ReporteInmueblesQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(ReportDateRange $range, array $filters): array
    {
        $base = $this->base($range, $filters);
        $total = (clone $base)->count();
        [$stateExpression, $stateBindings] = $this->stateExpression();
        $states = (clone $base)
            ->select([])
            ->selectRaw("{$stateExpression} AS estado", $stateBindings)
            ->get()
            ->countBy('estado');

        $distributionCategory = (clone $base)
            ->join('categorias', 'categorias.id', '=', 'inmuebles.categoria_id')
            ->select('inmuebles.categoria_id', 'categorias.nombre as categoria', DB::raw('COUNT(*) AS total'))
            ->groupBy('inmuebles.categoria_id', 'categorias.nombre')
            ->orderByDesc('total')
            ->orderBy('inmuebles.categoria_id')
            ->get()
            ->map(fn ($row): array => [
                'categoria_id' => (int) $row->categoria_id,
                'categoria' => $row->categoria,
                'total' => (int) $row->total,
            ])->all();

        $distributionZone = (clone $base)
            ->select('municipio as zona', DB::raw('COUNT(*) AS total'))
            ->groupBy('municipio')
            ->orderByDesc('total')
            ->orderBy('municipio')
            ->get()
            ->map(fn ($row): array => ['zona' => $row->zona, 'total' => (int) $row->total])->all();

        $agentLoad = (clone $base)
            ->join('agente_inmueble', 'agente_inmueble.inmueble_id', '=', 'inmuebles.id')
            ->join('agentes', 'agentes.id', '=', 'agente_inmueble.agente_id')
            ->join('users', 'users.id', '=', 'agentes.user_id')
            ->select('agentes.id as agente_id', DB::raw("TRIM(CONCAT(users.nombres, ' ', users.apellido_paterno, COALESCE(CONCAT(' ', users.apellido_materno), ''))) AS agente"), DB::raw('COUNT(DISTINCT inmuebles.id) AS total_inmuebles'))
            ->groupBy('agentes.id', 'users.nombres', 'users.apellido_paterno', 'users.apellido_materno')
            ->orderByDesc('total_inmuebles')
            ->orderBy('agentes.id')
            ->get()
            ->map(fn ($row): array => [
                'agente_id' => (int) $row->agente_id,
                'agente' => trim($row->agente),
                'total_inmuebles' => (int) $row->total_inmuebles,
            ])->all();

        $generatedAt = CarbonImmutable::now(config('app.timezone'));
        $ageExpression = $this->ageExpression($generatedAt);
        [$stateExpression, $stateBindings] = $this->stateExpression();
        $ageBands = (clone $base)
            ->whereRaw("{$stateExpression} = ?", [...$stateBindings, EstadoDisponibilidadInmueble::Disponible->value])
            ->select([])
            ->selectRaw("CASE WHEN {$ageExpression} <= 30 THEN '0-30' WHEN {$ageExpression} <= 90 THEN '31-90' WHEN {$ageExpression} <= 180 THEN '91-180' ELSE '181+' END AS rango, COUNT(*) AS total")
            ->groupByRaw("CASE WHEN {$ageExpression} <= 30 THEN '0-30' WHEN {$ageExpression} <= 90 THEN '31-90' WHEN {$ageExpression} <= 180 THEN '91-180' ELSE '181+' END")
            ->pluck('total', 'rango');

        $oldest = (clone $base)
            ->whereRaw("{$stateExpression} = ?", [...$stateBindings, EstadoDisponibilidadInmueble::Disponible->value])
            ->select('inmuebles.id', 'inmuebles.codigo', 'inmuebles.titulo', 'inmuebles.municipio as zona', 'categorias.nombre as categoria', DB::raw("{$ageExpression} AS dias_disponible"))
            ->join('categorias', 'categorias.id', '=', 'inmuebles.categoria_id')
            ->with(['asignacionesAgentes' => fn ($query) => $query->where('es_principal', true)->with('agente.user')])
            ->orderByDesc('dias_disponible')
            ->orderBy('inmuebles.id')
            ->limit(10)
            ->get()
            ->map(fn (Inmueble $property): array => [
                'id' => $property->id,
                'codigo' => $property->codigo,
                'titulo' => $property->titulo,
                'zona' => $property->zona,
                'categoria' => $property->categoria,
                'dias_disponible' => max(0, (int) $property->dias_disponible),
                'agente_principal' => $this->agentName($property->asignacionesAgentes->first()?->agente),
            ])->all();

        $stateRows = [];
        foreach (['disponibles' => 'disponible', 'vendidos' => 'vendido', 'rentados' => 'rentado', 'inactivos' => 'inactivo'] as $label => $state) {
            $count = (int) ($states->get($state) ?? 0);
            $stateRows[$label] = ['total' => $count, 'porcentaje' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0];
        }

        return [
            'reporte' => 'inmuebles',
            'generado_en' => $generatedAt->toISOString(),
            'filtros' => $this->filterEcho($filters, $range),
            'moneda' => 'MXN',
            'resumen' => ['total_inmuebles' => $total, 'estados' => $stateRows],
            'graficas' => [
                'distribucion_por_categoria' => $distributionCategory,
                'distribucion_por_zona' => $distributionZone,
                'carga_por_agente' => $agentLoad,
            ],
            'tablas' => [
                'antiguedad_disponibles' => array_map(fn (string $key): array => ['rango' => $key, 'total' => (int) ($ageBands->get($key) ?? 0)], ['0-30', '31-90', '91-180', '181+']),
                'inmuebles_disponibles_mas_antiguos' => $oldest,
            ],
        ];
    }

    private function base(ReportDateRange $range, array $filters): Builder
    {
        [$stateSql, $stateBindings] = $this->stateExpression();
        $query = Inmueble::query()
            ->select('inmuebles.*')
            ->selectRaw("{$stateSql} AS estado_reporte", $stateBindings)
            ->whereBetween('inmuebles.created_at', [$range->inicio, $range->fin]);

        if (isset($filters['zona'])) {
            $query->where('inmuebles.municipio', $filters['zona']);
        }
        if (isset($filters['categoria_id'])) {
            $query->where('inmuebles.categoria_id', $filters['categoria_id']);
        }
        if (isset($filters['agente_id'])) {
            $query->whereHas('asignacionesAgentes', fn (Builder $assignment) => $assignment->where('agente_id', $filters['agente_id']));
        }
        if (isset($filters['estado'])) {
            $query->whereRaw("{$stateSql} = ?", [...$stateBindings, $filters['estado']]);
        }

        return $query;
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function stateExpression(): array
    {
        $latestType = DB::table('operaciones as latest_ops')
            ->select('latest_ops.tipo_operacion')
            ->whereColumn('latest_ops.inmueble_id', 'inmuebles.id')
            ->where('latest_ops.estado', EstadoOperacion::Registrada->value)
            ->orderByDesc('latest_ops.fecha_operacion')
            ->orderByDesc('latest_ops.id')
            ->limit(1);

        return [
            "CASE WHEN ({$latestType->toSql()}) = 'venta' THEN 'vendido' WHEN ({$latestType->toSql()}) = 'renta' THEN 'rentado' WHEN inmuebles.estado_disponibilidad = 'disponible' THEN 'disponible' ELSE 'inactivo' END",
            [...$latestType->getBindings(), ...$latestType->getBindings()],
        ];
    }

    private function ageExpression(CarbonImmutable $generatedAt): string
    {
        return "GREATEST(0, DATEDIFF('{$generatedAt->toDateString()}', COALESCE(inmuebles.fecha_publicacion, inmuebles.created_at)))";
    }

    private function agentName(?Agente $agent): ?string
    {
        return $agent?->user === null ? null : trim(implode(' ', array_filter([
            $agent->user->nombres,
            $agent->user->apellido_paterno,
            $agent->user->apellido_materno,
        ])));
    }

    /** @return array<string, mixed> */
    private function filterEcho(array $filters, ReportDateRange $range): array
    {
        return ['desde' => $range->desde, 'hasta' => $range->hasta, ...array_intersect_key($filters, array_flip(['zona', 'categoria_id', 'estado', 'agente_id']))];
    }
}
