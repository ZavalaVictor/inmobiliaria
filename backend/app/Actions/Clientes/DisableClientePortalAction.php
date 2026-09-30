<?php

namespace App\Actions\Clientes;

use App\Enums\EstadoUsuario;
use App\Models\Cliente;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class DisableClientePortalAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /** @return array<string, mixed> */
    public function execute(User $actor, Cliente $cliente): array
    {
        return DB::transaction(function () use ($actor, $cliente): array {
            $lockedCliente = Cliente::query()->whereKey($cliente->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedCliente->user_id === null) {
                return [
                    'cliente_id' => $lockedCliente->getKey(),
                    'portal' => ['habilitado' => false, 'configurado' => false],
                ];
            }

            $user = User::withTrashed()->whereKey($lockedCliente->user_id)->lockForUpdate()->first();
            if ($user === null || $user->getRoleNames()->sort()->values()->all() !== ['Cliente'] || $user->trashed()) {
                throw new ConflictHttpException('La cuenta vinculada requiere resolución administrativa.');
            }

            if ($user->estado === EstadoUsuario::Activo) {
                $before = $user->estado?->value ?? $user->estado;
                $user->forceFill(['estado' => EstadoUsuario::Inactivo])->save();
                $this->bitacora->record($actor, 'cliente_portal_deshabilitado', 'cliente', $lockedCliente->getKey(), 'Portal Cliente deshabilitado.', ['user_id' => $user->getKey(), 'estado_cuenta' => $before], ['user_id' => $user->getKey(), 'estado_cuenta' => EstadoUsuario::Inactivo->value]);
            }

            return [
                'cliente_id' => $lockedCliente->getKey(),
                'portal' => [
                    'habilitado' => false,
                    'configurado' => true,
                    'user_id' => $user->getKey(),
                    'estado_cuenta' => $user->fresh()->estado?->value ?? $user->estado,
                ],
            ];
        });
    }
}
