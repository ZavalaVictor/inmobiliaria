<?php

namespace App\Services\Reportes;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ReporteAccess
{
    public function authorize(User $user, string $permission): void
    {
        if ((! $user->hasRole('Administrador') && ! $user->hasRole('Director General')) || ! $user->can($permission)) {
            throw new AuthorizationException('No autorizado para este reporte.');
        }
    }
}
