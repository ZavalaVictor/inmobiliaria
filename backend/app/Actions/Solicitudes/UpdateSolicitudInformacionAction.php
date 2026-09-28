<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoUsuario;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Validation\ValidationException;

final class UpdateSolicitudInformacionAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, SolicitudInformacion $solicitud, array $attributes): SolicitudInformacion
    {
        $allowed = ActorScope::isAgent($user)
            ? ['estado', 'fecha_atencion']
            : [
                'nombre',
                'email',
                'telefono',
                'mensaje',
                'medio_preferido',
                'estado',
                'atendida_por_user_id',
                'fecha_atencion',
            ];
        $values = array_intersect_key($attributes, array_flip($allowed));

        if (array_key_exists('atendida_por_user_id', $values)) {
            $this->validateAttendee($user, $values['atendida_por_user_id']);
        }

        $solicitud->update($values);

        return $solicitud->fresh([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'atendidaPor:id,nombres,apellido_paterno,apellido_materno',
        ]);
    }

    private function validateAttendee(User $user, mixed $attendeeId): void
    {
        if (! $user->hasAnyRole(['Administrador', 'Asistente'])) {
            throw ValidationException::withMessages([
                'atendida_por_user_id' => ['El usuario actual no puede asignar solicitudes.'],
            ]);
        }

        if ($attendeeId === null) {
            return;
        }

        $attendee = User::query()->whereKey($attendeeId)->first();
        if ($attendee === null
            || $attendee->estado !== EstadoUsuario::Activo
            || ! $attendee->can('solicitudes.actualizar')) {
            throw ValidationException::withMessages([
                'atendida_por_user_id' => ['El usuario seleccionado no puede atender solicitudes.'],
            ]);
        }
    }
}
