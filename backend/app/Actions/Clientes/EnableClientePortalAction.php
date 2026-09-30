<?php

namespace App\Actions\Clientes;

use App\Enums\EstadoUsuario;
use App\Models\Cliente;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\PortalCliente\ClientePortalActivationSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EnableClientePortalAction
{
    public function __construct(
        private readonly ClientePortalActivationSender $activationSender,
        private readonly BitacoraService $bitacora,
    ) {}

    /** @return array<string, mixed> */
    public function execute(User $actor, Cliente $cliente): array
    {
        $email = mb_strtolower(trim((string) $cliente->email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['email' => 'El Cliente debe tener un correo válido para habilitar el Portal.']);
        }

        $result = DB::transaction(function () use ($actor, $cliente, $email): array {
            $lockedCliente = Cliente::query()->whereKey($cliente->getKey())->lockForUpdate()->firstOrFail();
            $linkedUser = $lockedCliente->user_id === null
                ? null
                : User::withTrashed()->whereKey($lockedCliente->user_id)->lockForUpdate()->first();

            if ($linkedUser !== null) {
                $this->assertLinkedUser($lockedCliente, $linkedUser, $email);

                if ($linkedUser->estado === EstadoUsuario::Activo) {
                    return [
                        'cliente' => $lockedCliente,
                        'user' => $linkedUser,
                        'created' => false,
                        'rehabilitated' => false,
                        'already_enabled' => true,
                    ];
                }

                if ($linkedUser->estado !== EstadoUsuario::Inactivo || $linkedUser->trashed()) {
                    throw new ConflictHttpException('La cuenta vinculada requiere resolución administrativa.');
                }

                $linkedUser->forceFill(['estado' => EstadoUsuario::Activo])->save();
                $this->bitacora->record($actor, 'cliente_portal_habilitado', 'cliente', $lockedCliente->getKey(), 'Portal Cliente rehabilitado.', ['user_id' => $linkedUser->getKey(), 'estado_cuenta' => EstadoUsuario::Inactivo->value], ['user_id' => $linkedUser->getKey(), 'estado_cuenta' => EstadoUsuario::Activo->value]);

                return [
                    'cliente' => $lockedCliente,
                    'user' => $linkedUser->fresh(),
                    'created' => false,
                    'rehabilitated' => true,
                    'already_enabled' => false,
                ];
            }

            if (User::withTrashed()->where('email', $email)->lockForUpdate()->exists()) {
                throw new ConflictHttpException('Ya existe una cuenta con ese correo y requiere resolución administrativa.');
            }

            $user = User::create([
                'nombres' => $lockedCliente->nombres,
                'apellido_paterno' => $lockedCliente->apellido_paterno,
                'apellido_materno' => $lockedCliente->apellido_materno,
                'email' => $email,
                'telefono' => $lockedCliente->telefono,
                'password' => Str::random(96),
                'estado' => EstadoUsuario::Activo->value,
            ]);
            $user->syncRoles(['Cliente']);

            $lockedCliente->forceFill(['user_id' => $user->getKey()])->save();
            $this->bitacora->record($actor, 'cliente_portal_habilitado', 'cliente', $lockedCliente->getKey(), 'Portal Cliente habilitado.', null, ['user_id' => $user->getKey(), 'estado_cuenta' => EstadoUsuario::Activo->value]);

            return [
                'cliente' => $lockedCliente,
                'user' => $user->fresh(),
                'created' => true,
                'rehabilitated' => false,
                'already_enabled' => false,
            ];
        });

        if (! $result['created']) {
            return [
                'cliente_id' => $result['cliente']->getKey(),
                'portal' => [
                    'habilitado' => true,
                    'user_id' => $result['user']->getKey(),
                    'estado_cuenta' => EstadoUsuario::Activo->value,
                    'ya_habilitado' => $result['already_enabled'],
                    'rehabilitado' => $result['rehabilitated'],
                ],
            ];
        }

        $history = $this->activationSender->send($result['cliente'], $result['user'], $actor);

        return [
            'cliente_id' => $result['cliente']->getKey(),
            'portal' => [
                'habilitado' => true,
                'user_id' => $result['user']->getKey(),
                'estado_cuenta' => EstadoUsuario::Activo->value,
                'ya_habilitado' => false,
                'rehabilitado' => false,
            ],
            'correo' => [
                'estado' => $history->estado?->value ?? $history->estado,
            ],
        ];
    }

    private function assertLinkedUser(Cliente $cliente, User $user, string $email): void
    {
        $roles = $user->getRoleNames()->sort()->values()->all();
        if ($roles !== ['Cliente'] || mb_strtolower(trim((string) $user->email)) !== $email) {
            throw new ConflictHttpException('La cuenta vinculada requiere resolución administrativa.');
        }
    }
}
