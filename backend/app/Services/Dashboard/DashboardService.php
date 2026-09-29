<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Queries\Dashboard\DashboardAgenteQuery;
use App\Queries\Dashboard\DashboardAsistenteQuery;
use App\Queries\Dashboard\DashboardGlobalQuery;
use Illuminate\Auth\Access\AuthorizationException;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRoleResolver $roles,
        private readonly DashboardGlobalQuery $global,
        private readonly DashboardAgenteQuery $agent,
        private readonly DashboardAsistenteQuery $assistant,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, DashboardPeriod $period): array
    {
        $role = $this->roles->resolve($user);

        if ($role === null) {
            throw new AuthorizationException('No autorizado para consultar el dashboard.');
        }

        $payload = match ($role) {
            'Administrador' => $this->global->admin($period),
            'Director General' => $this->global->director($period),
            'Asistente' => $this->assistant->build($period),
            'Agente Inmobiliario' => $this->agent->build($user, $period),
        };

        return [
            'rol' => $role,
            'periodo' => $period->toArray(),
            'moneda' => 'MXN',
            ...$payload,
            'notificaciones' => [
                'no_leidas' => $user->unreadNotifications()->count(),
            ],
        ];
    }
}
