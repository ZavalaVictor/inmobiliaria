<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoUsuario;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Services\Solicitudes\SolicitudNotificationDispatcher;
use App\Support\Authorization\ActorScope;
use Illuminate\Validation\ValidationException;

final class UpdateSolicitudInformacionAction
{
    public function __construct(
        private readonly SolicitudNotificationDispatcher $notifications,
    ) {}

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
        $previousAttendeeId = $solicitud->atendida_por_user_id === null
            ? null
            : (int) $solicitud->atendida_por_user_id;
        $newAttendeeId = array_key_exists('atendida_por_user_id', $values)
            && $values['atendida_por_user_id'] !== null
            ? (int) $values['atendida_por_user_id']
            : null;
        $shouldNotifyAssignment = array_key_exists('atendida_por_user_id', $values)
            && $newAttendeeId !== null
            && $previousAttendeeId !== $newAttendeeId;

        if (array_key_exists('atendida_por_user_id', $values)) {
            $this->validateAttendee($user, $values['atendida_por_user_id']);
        }

        $solicitud->update($values);

        $updated = $solicitud->fresh([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'atendidaPor:id,nombres,apellido_paterno,apellido_materno',
        ]);

        if ($shouldNotifyAssignment) {
            $this->notifications->assigned($updated);
        }

        return $updated;
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
