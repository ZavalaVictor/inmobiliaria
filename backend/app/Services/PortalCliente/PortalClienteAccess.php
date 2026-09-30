<?php

namespace App\Services\PortalCliente;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class PortalClienteAccess
{
    public function authorize(User $user): Cliente
    {
        if (! $user->can('portal_cliente.ver') || $user->getRoleNames()->count() !== 1 || ! $user->hasRole('Cliente')) {
            throw new AuthorizationException('No tienes acceso al Portal Cliente.');
        }

        $clientes = Cliente::query()
            ->where('user_id', $user->getKey())
            ->limit(2)
            ->get();

        if ($clientes->count() !== 1) {
            throw new AuthorizationException('No tienes un perfil de Cliente válido.');
        }

        return $clientes->firstOrFail();
    }

    public function authorizeAdministrator(User $user): void
    {
        if (! $user->hasRole('Administrador') || ! $user->can('clientes.portal.gestionar')) {
            throw new AuthorizationException('No tienes permiso para gestionar el Portal Cliente.');
        }
    }
}
