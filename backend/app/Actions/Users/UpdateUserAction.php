<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes, ?User $actor = null): User
    {
        DB::transaction(function () use ($user, $attributes, $actor): void {
            $before = $this->snapshot($user);
            $user->fill(Arr::only($attributes, [
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'email',
                'telefono',
                'estado',
            ]));
            $user->save();

            $this->bitacora->record(
                $actor,
                'usuario_actualizado',
                'usuario',
                $user->getKey(),
                'Usuario actualizado.',
                $before,
                $this->snapshot($user),
            );
        });

        return $user->fresh(['roles:id,name,guard_name']);
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
