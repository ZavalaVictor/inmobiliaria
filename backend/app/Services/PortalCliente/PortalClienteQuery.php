<?php

namespace App\Services\PortalCliente;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class PortalClienteQuery
{
    /** @return array<string, mixed> */
    public function summary(User $user, Cliente $cliente): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        $interestTotal = $this->interestQuery($cliente)->count();
        $interests = $this->interestQuery($cliente)
            ->with($this->interestRelations())
            ->orderByDesc('fecha_interes')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $upcoming = $this->appointmentQuery($cliente)
            ->with($this->appointmentRelations())
            ->where('fecha_inicio', '>=', $now)
            ->whereIn('estado', [EstadoCita::Programada->value, EstadoCita::Confirmada->value])
            ->orderBy('fecha_inicio')
            ->limit(5)
            ->get();

        $history = $this->appointmentQuery($cliente)
            ->with($this->appointmentRelations())
            ->where(function (Builder $query) use ($now): void {
                $query->where('fecha_inicio', '<', $now)
                    ->orWhereIn('estado', [
                        EstadoCita::Completada->value,
                        EstadoCita::Cancelada->value,
                        EstadoCita::NoAsistio->value,
                    ]);
            })
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'cliente' => $cliente,
            'inmuebles_interes' => [
                'total' => $interestTotal,
                'items' => $interests,
            ],
            'citas' => [
                'proximas' => $upcoming,
                'historial_reciente' => $history,
            ],
            'notificaciones' => [
                'no_leidas' => $user->unreadNotifications()->count(),
            ],
        ];
    }

    public function interests(Cliente $cliente, int $perPage): LengthAwarePaginator
    {
        return $this->interestQuery($cliente)
            ->with($this->interestRelations())
            ->orderByDesc('fecha_interes')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function appointments(Cliente $cliente, string $type, int $perPage): LengthAwarePaginator
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $query = $this->appointmentQuery($cliente)->with($this->appointmentRelations());

        if ($type === 'proximas') {
            $query->where('fecha_inicio', '>=', $now)
                ->whereIn('estado', [EstadoCita::Programada->value, EstadoCita::Confirmada->value])
                ->orderBy('fecha_inicio');
        } else {
            $query->where(function (Builder $scope) use ($now): void {
                $scope->where('fecha_inicio', '<', $now)
                    ->orWhereIn('estado', [
                        EstadoCita::Completada->value,
                        EstadoCita::Cancelada->value,
                        EstadoCita::NoAsistio->value,
                    ]);
            })->orderByDesc('fecha_inicio');
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    private function interestQuery(Cliente $cliente): Builder
    {
        return ClienteInmuebleInteres::query()
            ->where('cliente_id', $cliente->getKey())
            ->whereHas('inmueble');
    }

    private function appointmentQuery(Cliente $cliente): Builder
    {
        return Cita::query()->where('cliente_id', $cliente->getKey());
    }

    /** @return array<int, string> */
    private function interestRelations(): array
    {
        return ['inmueble:id,categoria_id,codigo,titulo,slug,tipo_operacion,precio_venta,renta_mensual,municipio,estado_ubicacion,habitaciones,banos_completos,superficie_terreno_m2,superficie_construccion_m2,estado_disponibilidad', 'inmueble.categoria:id,nombre'];
    }

    /** @return array<int, string> */
    private function appointmentRelations(): array
    {
        return [
            'inmueble:id,categoria_id,codigo,titulo,slug,tipo_operacion,precio_venta,renta_mensual,municipio,estado_ubicacion,habitaciones,banos_completos,superficie_terreno_m2,superficie_construccion_m2,estado_disponibilidad',
            'inmueble.categoria:id,nombre',
            'agente:id,user_id',
            'agente.user:id,nombres,apellido_paterno,apellido_materno',
            'historial:id,cita_id,fecha_inicio_anterior,fecha_fin_anterior,fecha_inicio_nueva,fecha_fin_nueva,tipo_cambio,fecha_modificacion',
        ];
    }
}
