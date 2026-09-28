<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class CreateUserAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes, ?User $actor = null): User
    {
        $user = DB::transaction(function () use ($attributes, $actor): User {
            $user = User::create(Arr::only($attributes, [
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'email',
                'telefono',
                'password',
                'estado',
            ]));

            $this->bitacora->record(
                $actor,
                BitacoraService::ACTIONS[0],
                'usuario',
                $user->getKey(),
                'Usuario creado.',
                null,
                $this->snapshot($user),
            );

            return $user;
        });

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function snapshot(User $user): array
    {
        return [
            'nombres' => $user->nombres,
            'apellido_paterno' => $user->apellido_paterno,
            'apellido_materno' => $user->apellido_materno,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'estado' => $user->estado,
        ];
    }
}
