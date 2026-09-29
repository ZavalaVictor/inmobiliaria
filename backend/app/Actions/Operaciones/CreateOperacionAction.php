<?php

namespace App\Actions\Operaciones;

use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\Operaciones\OperacionNotificationDispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateOperacionAction
{
    public function __construct(private readonly BitacoraService $bitacora, private readonly OperacionNotificationDispatcher $notifications) {}

    public function execute(User $user, array $attributes): Operacion
    {
        $values = array_intersect_key($attributes, array_flip([
            'oportunidad_id', 'cliente_id', 'inmueble_id', 'tipo_operacion', 'monto',
            'fecha_operacion', 'fecha_inicio_contrato', 'fecha_fin_contrato', 'observaciones',
        ]));

        try {
            $operation = DB::transaction(function () use ($user, $values): Operacion {
                $opportunity = Oportunidad::query()
                    ->whereKey($values['oportunidad_id'])
                    ->lockForUpdate()
                    ->first();

                if ($opportunity === null) {
                    throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad no existe o no está disponible.']]);
                }

                if (Operacion::query()->where('oportunidad_id', $opportunity->getKey())->exists()) {
                    throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad ya tiene una Operación.']]);
                }

                $client = Cliente::query()->whereKey($values['cliente_id'])->first();
                $property = Inmueble::query()->whereKey($values['inmueble_id'])->first();

                if ($client === null) {
                    throw ValidationException::withMessages(['cliente_id' => ['El Cliente no existe o no está disponible.']]);
                }

                if ($property === null) {
                    throw ValidationException::withMessages(['inmueble_id' => ['El Inmueble no existe o no está disponible.']]);
                }

                if ((int) $opportunity->cliente_id !== (int) $client->getKey()) {
                    throw ValidationException::withMessages(['cliente_id' => ['El Cliente no corresponde a la Oportunidad.']]);
                }

                if ($opportunity->inmueble_id !== null && (int) $opportunity->inmueble_id !== (int) $property->getKey()) {
                    throw ValidationException::withMessages(['inmueble_id' => ['El Inmueble no corresponde a la Oportunidad.']]);
                }

                $propertyType = $property->tipo_operacion?->value ?? $property->tipo_operacion;

                if ($propertyType !== $values['tipo_operacion']) {
                    throw ValidationException::withMessages(['tipo_operacion' => ['El tipo de Operación debe coincidir con el tipo del Inmueble.']]);
                }

                $operationValues = [
                    'oportunidad_id' => $opportunity->getKey(),
                    'cliente_id' => $client->getKey(),
                    'inmueble_id' => $property->getKey(),
                    'registrado_por_user_id' => $user->getKey(),
                    'tipo_operacion' => $values['tipo_operacion'],
                    'monto' => $values['monto'],
                    'estado' => 'registrada',
                    'fecha_inicio_contrato' => $values['fecha_inicio_contrato'] ?? null,
                    'fecha_fin_contrato' => $values['fecha_fin_contrato'] ?? null,
                    'observaciones' => $values['observaciones'] ?? null,
                ];

                if (array_key_exists('fecha_operacion', $values)) {
                    $operationValues['fecha_operacion'] = $values['fecha_operacion'];
                }

                $operation = Operacion::create($operationValues);
                $this->bitacora->record($user, 'operacion_creada', 'operacion', $operation->getKey(), 'Operación creada.', null, $this->snapshot($operation));

                return $operation;
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'uq_operaciones_oportunidad')) {
                throw ValidationException::withMessages(['oportunidad_id' => ['La Oportunidad ya tiene una Operación.']]);
            }

            throw $exception;
        }

        $operation = $operation->fresh($this->relations());
        foreach ($operation->asignacionesAgentes as $assignment) {
            if ($assignment->agente !== null) {
                $this->notifications->assigned($operation, $assignment->agente, $user);
            }
        }

        return $operation;
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
            'asignacionesAgentes.agente:id,numero_empleado,user_id',
            'asignacionesAgentes.agente.user:id,nombres,apellido_paterno,apellido_materno,email',
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(Operacion $operacion): array
    {
        return [
            'oportunidad_id' => $operacion->oportunidad_id,
            'cliente_id' => $operacion->cliente_id,
            'inmueble_id' => $operacion->inmueble_id,
            'tipo_operacion' => $operacion->tipo_operacion,
            'monto' => $operacion->monto,
            'estado' => $operacion->estado,
            'fecha_operacion' => $operacion->fecha_operacion,
            'fecha_inicio_contrato' => $operacion->fecha_inicio_contrato,
            'fecha_fin_contrato' => $operacion->fecha_fin_contrato,
        ];
    }
}
