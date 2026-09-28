<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;

final class DeleteUserAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $before = [
                'nombres' => $user->nombres,
                'apellido_paterno' => $user->apellido_paterno,
                'apellido_materno' => $user->apellido_materno,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'estado' => $user->estado,
            ];
            $user->delete();

            $this->bitacora->record($actor, 'usuario_eliminado', 'usuario', $user->getKey(), 'Usuario eliminado.', $before);
        });
    }
}
