<?php

namespace App\Queries\Reportes;

use App\Enums\EstadoCita;
use App\Enums\EstadoOperacion;
use App\Enums\TipoOperacion;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\VisualizacionInmueble;
use App\Services\Reportes\ReportDateRange;

final class ReporteComparativoQuery
{
    /** @param array{a: ReportDateRange, b: ReportDateRange} $ranges */
    public function build(array $ranges): array
    {
        $values = ['clientes_nuevos' => fn (ReportDateRange $r): int => Cliente::query()->whereBetween('created_at', [$r->inicio, $r->fin])->count(), 'inmuebles_captados' => fn (ReportDateRange $r): int => Inmueble::query()->whereBetween('created_at', [$r->inicio, $r->fin])->count(), 'inmuebles_vendidos' => fn (ReportDateRange $r): int => Operacion::query()->where('estado', EstadoOperacion::Registrada->value)->where('tipo_operacion', TipoOperacion::Venta->value)->whereBetween('fecha_operacion', [$r->inicio, $r->fin])->distinct('inmueble_id')->count('inmueble_id'), 'inmuebles_rentados' => fn (ReportDateRange $r): int => Operacion::query()->where('estado', EstadoOperacion::Registrada->value)->where('tipo_operacion', TipoOperacion::Renta->value)->whereBetween('fecha_operacion', [$r->inicio, $r->fin])->distinct('inmueble_id')->count('inmueble_id'), 'citas_realizadas' => fn (ReportDateRange $r): int => Cita::query()->whereBetween('fecha_inicio', [$r->inicio, $r->fin])->where('estado', EstadoCita::Completada->value)->count(), 'citas_canceladas' => fn (ReportDateRange $r): int => Cita::query()->whereBetween('fecha_inicio', [$r->inicio, $r->fin])->where('estado', EstadoCita::Cancelada->value)->count(), 'consultas_propiedades' => fn (ReportDateRange $r): int => VisualizacionInmueble::query()->whereBetween('fecha_visualizacion', [$r->inicio, $r->fin])->count()];
        $indicators = [];
        foreach ($values as $name => $resolver) {
            $a = $resolver($ranges['a']);
            $b = $resolver($ranges['b']);
            $indicators[] = $this->indicator($name, $a, $b);
        }

        return ['reporte' => 'comparativo-periodos', 'generado_en' => now(config('app.timezone'))->toISOString(), 'filtros' => ['periodo_a' => $ranges['a']->toArray(), 'periodo_b' => $ranges['b']->toArray()], 'periodo_a' => $this->period($ranges['a']), 'periodo_b' => $this->period($ranges['b']), 'indicadores' => $indicators];
    }

    /** @return array<string, mixed> */
    private function indicator(string $name, int $a, int $b): array
    {
        if ($a === 0 && $b > 0) {
            return ['indicador' => $name, 'periodo_a' => $a, 'periodo_b' => $b, 'diferencia_absoluta' => $b - $a, 'diferencia_porcentual' => null, 'no_calculable' => true, 'tendencia' => 'aumento'];
        }
        $diff = $b - $a;

        return ['indicador' => $name, 'periodo_a' => $a, 'periodo_b' => $b, 'diferencia_absoluta' => $diff, 'diferencia_porcentual' => $a === 0 ? 0.0 : round(($diff / $a) * 100, 2), 'no_calculable' => false, 'tendencia' => $diff > 0 ? 'aumento' : ($diff < 0 ? 'disminucion' : 'sin_cambio')];
    }

    /** @return array{desde: string, hasta: string, duracion_dias: int} */
    private function period(ReportDateRange $range): array
    {
        return [...$range->toArray(), 'duracion_dias' => $range->inicio->diffInDays($range->fin->startOfDay()) + 1];
    }
}
