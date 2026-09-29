<?php

namespace App\Queries\Dashboard;

use App\Enums\EstadoCita;
use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\EstadoOperacion;
use App\Enums\EstadoOportunidad;
use App\Enums\EstadoSolicitudInformacion;
use App\Enums\EtapaOportunidad;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\SolicitudInformacion;
use App\Services\Dashboard\DashboardPeriod;
use Illuminate\Database\Eloquent\Builder;

final class DashboardGlobalQuery
{
    /**
     * @return array<string, mixed>
     */
    public function admin(DashboardPeriod $period): array
    {
        return [
            'resumen' => [
                'inmuebles_totales' => Inmueble::query()->count(),
                'inmuebles_disponibles' => Inmueble::query()->where('estado_disponibilidad', EstadoDisponibilidadInmueble::Disponible->value)->count(),
                'clientes_totales' => Cliente::query()->count(),
                'oportunidades_abiertas' => Oportunidad::query()->where('estado', EstadoOportunidad::Activa->value)->count(),
                'solicitudes_nuevas' => $this->requestsInPeriod($period),
                'citas_proximas' => $this->upcomingAppointments($period),
                ...$this->closedOperations($period),
            ],
            'graficas' => [
                'oportunidades_por_etapa' => $this->opportunitiesByStage(),
                'inmuebles_por_estado' => $this->propertiesByState(),
                'operaciones_por_mes' => $this->operationsByMonth($period),
            ],
            'listas' => $this->operationalLists($period),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function director(DashboardPeriod $period): array
    {
        return [
            'resumen' => [
                'inmuebles_totales' => Inmueble::query()->count(),
                'clientes_totales' => Cliente::query()->count(),
                'oportunidades_abiertas' => Oportunidad::query()->where('estado', EstadoOportunidad::Activa->value)->count(),
                ...$this->closedOperations($period),
                'solicitudes_periodo' => $this->requestsInPeriod($period),
                'citas_periodo' => $this->appointmentsInPeriod($period),
            ],
            'graficas' => [
                'oportunidades_por_etapa' => $this->opportunitiesByStage(),
                'inmuebles_por_estado' => $this->propertiesByState(),
                'operaciones_por_mes' => $this->operationsByMonth($period),
            ],
            'listas' => [],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function assistantSummary(DashboardPeriod $period): array
    {
        return [
            'solicitudes_nuevas' => SolicitudInformacion::query()->where('estado', EstadoSolicitudInformacion::Nueva->value)->count(),
            'solicitudes_en_atencion' => SolicitudInformacion::query()->where('estado', EstadoSolicitudInformacion::EnAtencion->value)->count(),
            'citas_hoy' => Cita::query()
                ->whereDate('fecha_inicio', $period->ahora->toDateString())
                ->where('estado', '!=', EstadoCita::Cancelada->value)
                ->count(),
            'citas_proximas' => $this->upcomingAppointments($period),
            'inmuebles_disponibles' => Inmueble::query()->where('estado_disponibilidad', EstadoDisponibilidadInmueble::Disponible->value)->count(),
            'clientes_totales' => Cliente::query()->count(),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function operationalLists(DashboardPeriod $period): array
    {
        return [
            'solicitudes_recientes' => SolicitudInformacion::query()
                ->with(['inmueble:id,codigo,titulo,slug'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(fn (SolicitudInformacion $request): array => $this->mapRequest($request))
                ->values()
                ->all(),
            'citas_proximas' => $this->upcomingAppointmentList(Cita::query(), $period),
        ];
    }

    /**
     * @return array{operaciones_registradas: int, monto_operaciones_registradas: float}
     */
    public function closedOperations(DashboardPeriod $period): array
    {
        $aggregate = $this->operationsInPeriodQuery($period)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(monto), 0) as monto')
            ->first();

        return [
            'operaciones_registradas' => (int) ($aggregate?->total ?? 0),
            'monto_operaciones_registradas' => $this->money($aggregate?->monto),
        ];
    }

    public function requestsInPeriod(DashboardPeriod $period): int
    {
        return SolicitudInformacion::query()
            ->whereBetween('fecha_solicitud', [$period->inicio, $period->fin])
            ->count();
    }

    public function appointmentsInPeriod(DashboardPeriod $period): int
    {
        return Cita::query()
            ->whereBetween('fecha_inicio', [$period->inicio, $period->fin])
            ->where('estado', '!=', EstadoCita::Cancelada->value)
            ->count();
    }

    public function upcomingAppointments(DashboardPeriod $period, ?Builder $base = null): int
    {
        return $this->upcomingAppointmentQuery($base ?? Cita::query(), $period)->count();
    }

    /**
     * @return array<int, array{etapa: string, total: int}>
     */
    public function opportunitiesByStage(?Builder $base = null): array
    {
        $counts = ($base ?? Oportunidad::query())
            ->selectRaw('etapa, COUNT(*) as total')
            ->groupBy('etapa')
            ->pluck('total', 'etapa');

        return array_map(
            fn (EtapaOportunidad $stage): array => [
                'etapa' => $stage->value,
                'total' => (int) ($counts->get($stage->value) ?? 0),
            ],
            EtapaOportunidad::cases(),
        );
    }

    /**
     * @return array<int, array{estado: string, total: int}>
     */
    public function propertiesByState(?Builder $base = null): array
    {
        $counts = ($base ?? Inmueble::query())
            ->selectRaw('estado_disponibilidad, COUNT(*) as total')
            ->groupBy('estado_disponibilidad')
            ->pluck('total', 'estado_disponibilidad');

        return array_map(
            fn (EstadoDisponibilidadInmueble $state): array => [
                'estado' => $state->value,
                'total' => (int) ($counts->get($state->value) ?? 0),
            ],
            EstadoDisponibilidadInmueble::cases(),
        );
    }

    /**
     * @return array<int, array{mes: string, total: int, monto: float}>
     */
    public function operationsByMonth(DashboardPeriod $period, ?Builder $base = null): array
    {
        $start = $period->lastTwelveMonthsStart();
        $end = $period->ahora->endOfMonth();
        $query = $base ?? Operacion::query();
        $rows = $query
            ->where('estado', EstadoOperacion::Registrada->value)
            ->whereBetween('fecha_operacion', [$start, $end])
            ->selectRaw("DATE_FORMAT(fecha_operacion, '%Y-%m') as mes, COUNT(*) as total, COALESCE(SUM(monto), 0) as monto")
            ->groupByRaw("DATE_FORMAT(fecha_operacion, '%Y-%m')")
            ->get()
            ->keyBy('mes');

        $months = [];
        for ($index = 0; $index < 12; $index++) {
            $month = $start->addMonths($index);
            $key = $month->format('Y-m');
            $row = $rows->get($key);
            $months[] = [
                'mes' => $key,
                'total' => (int) ($row?->total ?? 0),
                'monto' => $this->money($row?->monto),
            ];
        }

        return $months;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapRequest(SolicitudInformacion $request): array
    {
        return [
            'id' => $request->id,
            'nombre' => $request->nombre,
            'estado' => $request->estado?->value ?? $request->estado,
            'origen' => $request->origen,
            'inmueble' => $request->inmueble === null ? null : [
                'id' => $request->inmueble->id,
                'codigo' => $request->inmueble->codigo,
                'titulo' => $request->inmueble->titulo,
                'slug' => $request->inmueble->slug,
            ],
            'created_at' => $request->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingAppointmentList(Builder $query, DashboardPeriod $period): array
    {
        return $this->upcomingAppointmentQuery($query, $period)
            ->with([
                'cliente:id,nombres,apellido_paterno,apellido_materno',
                'agente:id,numero_empleado,user_id',
                'agente.user:id,nombres,apellido_paterno,apellido_materno',
                'inmueble:id,codigo,titulo,slug',
            ])
            ->orderBy('fecha_inicio')
            ->limit(5)
            ->get()
            ->map(fn (Cita $appointment): array => $this->mapAppointment($appointment))
            ->values()
            ->all();
    }

    private function operationsInPeriodQuery(DashboardPeriod $period): Builder
    {
        return Operacion::query()
            ->where('estado', EstadoOperacion::Registrada->value)
            ->whereBetween('fecha_operacion', [$period->inicio, $period->fin]);
    }

    private function upcomingAppointmentQuery(Builder $query, DashboardPeriod $period): Builder
    {
        return $query
            ->where('fecha_inicio', '>=', $period->ahora)
            ->where('fecha_inicio', '<=', $period->fin)
            ->where('estado', '!=', EstadoCita::Cancelada->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAppointment(Cita $appointment): array
    {
        return [
            'id' => $appointment->id,
            'fecha_inicio' => $appointment->fecha_inicio?->toISOString(),
            'fecha_fin' => $appointment->fecha_fin?->toISOString(),
            'estado' => $appointment->estado?->value ?? $appointment->estado,
            'cliente' => $appointment->cliente === null ? null : [
                'id' => $appointment->cliente->id,
                'nombres' => $appointment->cliente->nombres,
                'apellido_paterno' => $appointment->cliente->apellido_paterno,
                'apellido_materno' => $appointment->cliente->apellido_materno,
            ],
            'agente' => $appointment->agente === null ? null : [
                'id' => $appointment->agente->id,
                'numero_empleado' => $appointment->agente->numero_empleado,
                'user' => $appointment->agente->user === null ? null : [
                    'id' => $appointment->agente->user->id,
                    'nombres' => $appointment->agente->user->nombres,
                    'apellido_paterno' => $appointment->agente->user->apellido_paterno,
                    'apellido_materno' => $appointment->agente->user->apellido_materno,
                ],
            ],
            'inmueble' => $appointment->inmueble === null ? null : [
                'id' => $appointment->inmueble->id,
                'codigo' => $appointment->inmueble->codigo,
                'titulo' => $appointment->inmueble->titulo,
                'slug' => $appointment->inmueble->slug,
            ],
        ];
    }

    private function money(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
