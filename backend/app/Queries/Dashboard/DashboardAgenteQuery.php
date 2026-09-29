<?php

namespace App\Queries\Dashboard;

use App\Enums\EstadoOperacion;
use App\Enums\EstadoSolicitudInformacion;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Queries\Visibility\VisibleCitasQuery;
use App\Queries\Visibility\VisibleClientesQuery;
use App\Queries\Visibility\VisibleInmueblesQuery;
use App\Queries\Visibility\VisibleOperacionesQuery;
use App\Queries\Visibility\VisibleOportunidadesQuery;
use App\Queries\Visibility\VisibleSolicitudesQuery;
use App\Services\Dashboard\DashboardPeriod;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DashboardAgenteQuery
{
    public function __construct(private readonly DashboardGlobalQuery $global) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, DashboardPeriod $period): array
    {
        if (ActorScope::agentId($user) === null) {
            throw new AccessDeniedHttpException('Perfil de agente no disponible.');
        }

        $inmuebles = (new VisibleInmueblesQuery($user))->apply(Inmueble::query());
        $clientes = (new VisibleClientesQuery($user))->apply(Cliente::query());
        $solicitudes = (new VisibleSolicitudesQuery($user))->apply(SolicitudInformacion::query());
        $citas = (new VisibleCitasQuery($user))->apply(Cita::query());
        $oportunidades = (new VisibleOportunidadesQuery($user))->apply(Oportunidad::query());
        $operaciones = (new VisibleOperacionesQuery($user))->apply(Operacion::query());

        return [
            'resumen' => [
                'inmuebles_asignados' => (clone $inmuebles)->count(),
                'clientes_asignados' => (clone $clientes)->count(),
                'solicitudes_en_atencion' => (clone $solicitudes)->where('estado', EstadoSolicitudInformacion::EnAtencion->value)->count(),
                'citas_proximas' => $this->global->upcomingAppointments($period, $citas),
                'oportunidades_activas' => (clone $oportunidades)->where('estado', 'activa')->count(),
                'operaciones_propias' => (clone $operaciones)
                    ->where('estado', EstadoOperacion::Registrada->value)
                    ->whereBetween('fecha_operacion', [$period->inicio, $period->fin])
                    ->count(),
            ],
            'graficas' => [
                'oportunidades_por_etapa' => $this->global->opportunitiesByStage(clone $oportunidades),
                'operaciones_por_mes' => $this->global->operationsByMonth($period, clone $operaciones),
            ],
            'listas' => [
                'proximas_citas' => $this->global->upcomingAppointmentList($citas, $period),
                'solicitudes_pendientes' => $this->requestList((clone $solicitudes)->whereIn('estado', [
                    EstadoSolicitudInformacion::Nueva->value,
                    EstadoSolicitudInformacion::EnAtencion->value,
                ])),
                'operaciones_recientes' => $this->operationList(clone $operaciones),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requestList(Builder $query): array
    {
        return $query
            ->with(['inmueble:id,codigo,titulo,slug'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (SolicitudInformacion $request): array => $this->global->mapRequest($request))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function operationList(Builder $query): array
    {
        return $query
            ->with([
                'cliente:id,nombres,apellido_paterno,apellido_materno',
                'inmueble:id,codigo,titulo,slug',
            ])
            ->latest('fecha_operacion')
            ->limit(5)
            ->get()
            ->map(fn (Operacion $operation): array => [
                'id' => $operation->id,
                'estado' => $operation->estado?->value ?? $operation->estado,
                'fecha_operacion' => $operation->fecha_operacion?->toISOString(),
                'cliente' => $operation->cliente === null ? null : [
                    'id' => $operation->cliente->id,
                    'nombres' => $operation->cliente->nombres,
                    'apellido_paterno' => $operation->cliente->apellido_paterno,
                    'apellido_materno' => $operation->cliente->apellido_materno,
                ],
                'inmueble' => $operation->inmueble === null ? null : [
                    'id' => $operation->inmueble->id,
                    'codigo' => $operation->inmueble->codigo,
                    'titulo' => $operation->inmueble->titulo,
                    'slug' => $operation->inmueble->slug,
                ],
            ])
            ->values()
            ->all();
    }
}
