<?php

namespace App\Actions\Oportunidades;

use App\Models\Agente;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Oportunidad;
use App\Models\OportunidadHistorial;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateOportunidadAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): Oportunidad
    {
        $values = array_intersect_key($attributes, array_flip([
            'cliente_id',
            'inmueble_id',
            'agente_principal_id',
            'solicitud_informacion_id',
            'titulo',
            'notas',
        ]));

        try {
            $oportunidad = DB::transaction(function () use ($user, $values): Oportunidad {
                $cliente = Cliente::query()
                    ->whereKey($values['cliente_id'])
                    ->lockForUpdate()
                    ->first();

                if ($cliente === null) {
                    throw ValidationException::withMessages([
                        'cliente_id' => ['El Cliente seleccionado no existe o no está disponible.'],
                    ]);
                }

                $inmueble = $this->findInmueble($values['inmueble_id'] ?? null);
                $agentePrincipalId = $this->resolvePrincipalAgent($user, $values);
                $solicitud = $this->findAndValidateSolicitud($user, $values, $cliente, $inmueble);

                $this->validateAgentScope($user, $cliente, $inmueble);

                if ($solicitud !== null) {
                    $this->ensureSolicitudIsAvailable($solicitud->getKey());
                }

                $oportunidad = Oportunidad::create([
                    'cliente_id' => $cliente->getKey(),
                    'inmueble_id' => $inmueble?->getKey(),
                    'agente_principal_id' => $agentePrincipalId,
                    'solicitud_informacion_id' => $solicitud?->getKey(),
                    'titulo' => $values['titulo'],
                    'notas' => $values['notas'] ?? null,
                    'etapa' => 'contacto_inicial',
                    'estado' => 'activa',
                ]);

                OportunidadHistorial::create([
                    'oportunidad_id' => $oportunidad->getKey(),
                    'cambiado_por_user_id' => $user->getKey(),
                    'tipo_evento' => 'creacion',
                    'etapa_anterior' => null,
                    'etapa_nueva' => 'contacto_inicial',
                    'estado_anterior' => null,
                    'estado_nuevo' => 'activa',
                    'comentario' => null,
                ]);

                return $oportunidad;
            });
        } catch (QueryException $exception) {
            if ($this->isSolicitudUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'solicitud_informacion_id' => ['La Solicitud de Información ya está vinculada a una Oportunidad.'],
                ]);
            }

            throw $exception;
        }

        return $oportunidad->fresh($this->relations());
    }

    private function findInmueble(mixed $inmuebleId): ?Inmueble
    {
        if ($inmuebleId === null) {
            return null;
        }

        $inmueble = Inmueble::query()->whereKey($inmuebleId)->first();

        if ($inmueble === null) {
            throw ValidationException::withMessages([
                'inmueble_id' => ['El Inmueble seleccionado no existe o no está disponible.'],
            ]);
        }

        return $inmueble;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function resolvePrincipalAgent(User $user, array $values): ?int
    {
        if (ActorScope::isAgent($user)) {
            $agentId = ActorScope::agentId($user);

            if ($agentId === null) {
                throw ValidationException::withMessages([
                    'agente_principal_id' => ['El perfil Agente del usuario no está disponible.'],
                ]);
            }

            return $agentId;
        }

        if (! array_key_exists('agente_principal_id', $values) || $values['agente_principal_id'] === null) {
            return null;
        }

        if (! Agente::query()->whereKey($values['agente_principal_id'])->exists()) {
            throw ValidationException::withMessages([
                'agente_principal_id' => ['El Agente seleccionado no existe o no está disponible.'],
            ]);
        }

        return (int) $values['agente_principal_id'];
    }

    private function findAndValidateSolicitud(
        User $user,
        array $values,
        Cliente $cliente,
        ?Inmueble $inmueble,
    ): ?SolicitudInformacion {
        if (! array_key_exists('solicitud_informacion_id', $values) || $values['solicitud_informacion_id'] === null) {
            return null;
        }

        $solicitud = SolicitudInformacion::query()
            ->whereKey($values['solicitud_informacion_id'])
            ->first();

        if ($solicitud === null) {
            throw ValidationException::withMessages([
                'solicitud_informacion_id' => ['La Solicitud de Información no existe o no está disponible.'],
            ]);
        }

        if ($solicitud->cliente_id !== null && $solicitud->cliente_id !== $cliente->getKey()) {
            throw ValidationException::withMessages([
                'solicitud_informacion_id' => ['La Solicitud no corresponde al Cliente seleccionado.'],
            ]);
        }

        if ($solicitud->inmueble_id !== null && $solicitud->inmueble_id !== $inmueble?->getKey()) {
            throw ValidationException::withMessages([
                'solicitud_informacion_id' => ['La Solicitud no corresponde al Inmueble seleccionado.'],
            ]);
        }

        if (ActorScope::isAgent($user) && Gate::forUser($user)->denies('view', $solicitud)) {
            throw ValidationException::withMessages([
                'solicitud_informacion_id' => ['El Agente no tiene alcance sobre la Solicitud seleccionada.'],
            ]);
        }

        return $solicitud;
    }

    private function ensureSolicitudIsAvailable(int $solicitudId): void
    {
        if (Oportunidad::withTrashed()->where('solicitud_informacion_id', $solicitudId)->exists()) {
            throw ValidationException::withMessages([
                'solicitud_informacion_id' => ['La Solicitud de Información ya está vinculada a una Oportunidad.'],
            ]);
        }
    }

    private function validateAgentScope(User $user, Cliente $cliente, ?Inmueble $inmueble): void
    {
        if (! ActorScope::isAgent($user)) {
            return;
        }

        $agentId = ActorScope::agentId($user);
        $controlsClient = $agentId !== null
            && $cliente->asignacionesAgentes()->where('agente_id', $agentId)->exists();
        $controlsInmueble = $inmueble === null
            || ($agentId !== null && $inmueble->asignacionesAgentes()->where('agente_id', $agentId)->exists());

        if (! $controlsClient || ! $controlsInmueble) {
            throw ValidationException::withMessages([
                'cliente_id' => ['El Agente no tiene alcance sobre el Cliente y el Inmueble seleccionados.'],
            ]);
        }
    }

    private function isSolicitudUniqueViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'uq_oportunidades_solicitud');
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'agentePrincipal:id,numero_empleado,user_id',
            'agentePrincipal.user:id,nombres,apellido_paterno,apellido_materno',
            'solicitudInformacion:id,nombre,estado,medio_preferido',
        ];
    }
}
