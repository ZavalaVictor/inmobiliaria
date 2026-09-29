<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoSolicitudInformacion;
use App\Models\Cliente;
use App\Models\Inmueble;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Services\Solicitudes\SolicitudNotificationDispatcher;
use App\Support\Authorization\ActorScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateSolicitudInformacionAction
{
    public function __construct(private readonly SolicitudNotificationDispatcher $notifications) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): SolicitudInformacion
    {
        $values = array_intersect_key($attributes, array_flip([
            'cliente_id',
            'inmueble_id',
            'nombre',
            'email',
            'telefono',
            'mensaje',
            'medio_preferido',
        ]));

        if (ActorScope::isClient($user)) {
            $cliente = $user->cliente;

            if ($cliente === null) {
                throw ValidationException::withMessages([
                    'cliente_id' => ['El perfil Cliente del usuario no está disponible.'],
                ]);
            }

            $values['cliente_id'] = $cliente->getKey();
        }

        $this->validateRelations($user, $values);

        $solicitud = SolicitudInformacion::create([
            ...$values,
            'estado' => EstadoSolicitudInformacion::Nueva->value,
            'origen' => ActorScope::isClient($user) ? 'portal_cliente' : 'registro_interno',
            'atendida_por_user_id' => null,
            'fecha_atencion' => null,
        ])->fresh($this->relations());

        $this->notifications->created($solicitud, $user);

        return $solicitud;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function validateRelations(User $user, array $values): void
    {
        if (array_key_exists('cliente_id', $values) && $values['cliente_id'] !== null) {
            if (! Cliente::query()->whereKey($values['cliente_id'])->exists()) {
                throw ValidationException::withMessages([
                    'cliente_id' => ['El Cliente seleccionado no existe o no está disponible.'],
                ]);
            }
        }

        if (! array_key_exists('inmueble_id', $values) || $values['inmueble_id'] === null) {
            return;
        }

        $inmueble = Inmueble::query()->whereKey($values['inmueble_id'])->first();
        if ($inmueble === null) {
            throw ValidationException::withMessages([
                'inmueble_id' => ['El Inmueble seleccionado no existe o no está disponible.'],
            ]);
        }

        if (ActorScope::isClient($user)
            && ! $inmueble->publicado
            && Gate::forUser($user)->denies('view', $inmueble)) {
            throw ValidationException::withMessages([
                'inmueble_id' => ['El Inmueble no está disponible para este Cliente.'],
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'atendidaPor:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
