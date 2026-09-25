<?php

namespace App\Support\Authorization;

use App\Models\User;

final class ActorScope
{
    public static function isAgent(User $user): bool
    {
        return $user->hasRole('Agente Inmobiliario') && ! self::isGlobal($user);
    }

    public static function isClient(User $user): bool
    {
        return $user->hasRole('Cliente') && ! self::isGlobal($user);
    }

    public static function isGlobal(User $user): bool
    {
        return $user->hasAnyRole([
            'Administrador',
            'Asistente',
            'Director General',
        ]);
    }

    public static function agentId(User $user): ?int
    {
        return self::isAgent($user) ? $user->agente?->getKey() : null;
    }

    public static function clientId(User $user): ?int
    {
        return self::isClient($user) ? $user->cliente?->getKey() : null;
    }
}
