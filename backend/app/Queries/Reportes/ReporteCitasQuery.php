<?php

namespace App\Queries\Reportes;

use App\Enums\EstadoCita;
use App\Enums\TipoCambioCita;
use App\Models\Cita;
use App\Models\CitaHistorial;
use App\Services\Reportes\ReportDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ReporteCitasQuery
{
    /** @param array<string, mixed> $filters */
    public function build(ReportDateRange $range, array $filters): array
    {
        $base = $this->base($range, $filters);
        $counts = (clone $base)->select('estado', DB::raw('COUNT(*) AS total'))->groupBy('estado')->pluck('total', 'estado');
        $reprogramadas = CitaHistorial::query()
            ->whereBetween('fecha_modificacion', [$range->inicio, $range->fin])
            ->whereIn('tipo_cambio', [TipoCambioCita::Reprogramacion->value, TipoCambioCita::ReprogramacionYAgente->value])
            ->whereIn('cita_id', (clone $base)->select('citas.id'))
            ->distinct('cita_id')
            ->count('cita_id');
        $realizadas = (int) ($counts->get(EstadoCita::Completada->value) ?? 0);
        $canceladas = (int) ($counts->get(EstadoCita::Cancelada->value) ?? 0);
        $noAsistio = (int) ($counts->get(EstadoCita::NoAsistio->value) ?? 0);
        $resultadoFinal = $realizadas + $canceladas + $noAsistio;
        $programadas = (int) ($counts->get(EstadoCita::Programada->value) ?? 0) + (int) ($counts->get(EstadoCita::Confirmada->value) ?? 0);

        $hours = (clone $base)
            ->selectRaw("DATE_FORMAT(fecha_inicio, '%H:00') AS hora, COUNT(*) AS total")
            ->groupByRaw("DATE_FORMAT(fecha_inicio, '%H:00')")
            ->orderByDesc('total')->orderBy('hora')->get()
            ->map(fn ($row): array => ['hora' => $row->hora, 'total' => (int) $row->total])->all();

        $visits = (clone $base)->where('estado', EstadoCita::Completada->value)
            ->join('inmuebles', 'inmuebles.id', '=', 'citas.inmueble_id')
            ->select('citas.inmueble_id', 'inmuebles.codigo', 'inmuebles.titulo', DB::raw('COUNT(*) AS total_visitas'))
            ->groupBy('citas.inmueble_id', 'inmuebles.codigo', 'inmuebles.titulo')
            ->orderByDesc('total_visitas')->orderBy('citas.inmueble_id')->limit(10)->get()
            ->map(fn ($row): array => ['inmueble_id' => (int) $row->inmueble_id, 'codigo' => $row->codigo, 'titulo' => $row->titulo, 'total_visitas' => (int) $row->total_visitas])->all();

        $reprogrammedByAgent = CitaHistorial::query()
            ->join('citas', 'citas.id', '=', 'cita_historial.cita_id')
            ->whereBetween('cita_historial.fecha_modificacion', [$range->inicio, $range->fin])
            ->whereIn('cita_historial.tipo_cambio', [TipoCambioCita::Reprogramacion->value, TipoCambioCita::ReprogramacionYAgente->value])
            ->whereIn('cita_historial.cita_id', (clone $base)->select('citas.id'))
            ->select('citas.agente_id', DB::raw('COUNT(DISTINCT cita_historial.cita_id) AS total'))
            ->groupBy('citas.agente_id')
            ->pluck('total', 'agente_id');

        $activity = (clone $base)
            ->join('agentes', 'agentes.id', '=', 'citas.agente_id')
            ->join('users', 'users.id', '=', 'agentes.user_id')
            ->select('agentes.id as agente_id', DB::raw("TRIM(CONCAT(users.nombres, ' ', users.apellido_paterno, COALESCE(CONCAT(' ', users.apellido_materno), ''))) AS agente"), DB::raw('COUNT(*) AS total_citas'))
            ->selectRaw("SUM(CASE WHEN citas.estado = 'completada' THEN 1 ELSE 0 END) AS realizadas")
            ->selectRaw("SUM(CASE WHEN citas.estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas")
            ->groupBy('agentes.id', 'users.nombres', 'users.apellido_paterno', 'users.apellido_materno')
            ->orderBy('agentes.id')->get()->map(fn ($row): array => ['agente_id' => (int) $row->agente_id, 'agente' => trim($row->agente), 'total_citas' => (int) $row->total_citas, 'realizadas' => (int) $row->realizadas, 'canceladas' => (int) $row->canceladas, 'reprogramadas' => (int) ($reprogrammedByAgent->get($row->agente_id) ?? 0)])->all();

        return [
            'reporte' => 'citas',
            'generado_en' => now(config('app.timezone'))->toISOString(),
            'filtros' => ['desde' => $range->desde, 'hasta' => $range->hasta, ...array_intersect_key($filters, array_flip(['agente_id', 'inmueble_id', 'estado']))],
            'resumen' => [
                'citas_programadas' => $programadas,
                'citas_realizadas' => $realizadas,
                'citas_canceladas' => $canceladas,
                'citas_reprogramadas' => $reprogramadas,
                'porcentaje_cumplimiento' => $resultadoFinal > 0 ? round(($realizadas / $resultadoFinal) * 100, 2) : 0.0,
                'cumplimiento_numerador' => $realizadas,
                'cumplimiento_denominador' => $resultadoFinal,
            ],
            'graficas' => ['horarios_con_mayor_demanda' => $hours, 'actividad_por_agente' => $activity],
            'tablas' => ['inmuebles_con_mas_visitas' => $visits],
        ];
    }

    private function base(ReportDateRange $range, array $filters): Builder
    {
        $query = Cita::query()->whereBetween('citas.fecha_inicio', [$range->inicio, $range->fin]);
        foreach (['agente_id', 'inmueble_id', 'estado'] as $field) {
            if (isset($filters[$field])) {
                $query->where("citas.{$field}", $filters[$field]);
            }
        }

        return $query;
    }
}
