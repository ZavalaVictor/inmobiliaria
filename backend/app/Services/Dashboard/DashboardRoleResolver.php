<?php

namespace App\Services\Dashboard;

use App\Models\User;

final class DashboardRoleResolver
{
    /**
     * @var array<int, string>
     */
    private const PRECEDENCE = [
        'Administrador',
        'Director General',
        'Asistente',
        'Agente Inmobiliario',
    ];

    public function resolve(User $user): ?string
    {
        if (! $user->can('dashboard.ver')) {
            return null;
        }

        foreach (self::PRECEDENCE as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return null;
    }
}
