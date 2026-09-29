<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Respaldos\UpdateConfiguracionRespaldoAction;
use App\Exceptions\MultipleBackupConfigurationsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Respaldo\UpdateConfiguracionRespaldoRequest;
use App\Http\Resources\ConfiguracionRespaldoResource;
use App\Models\User;
use App\Services\ConfiguracionRespaldoResolver;
use Illuminate\Http\Response;

class ConfiguracionRespaldoController extends Controller
{
    public function show(ConfiguracionRespaldoResolver $resolver): ConfiguracionRespaldoResource
    {
        $this->authorizePermission(request()->user(), 'configuracion_respaldos.ver');

        try {
            return new ConfiguracionRespaldoResource($resolver->resolve());
        } catch (MultipleBackupConfigurationsException $exception) {
            abort(Response::HTTP_CONFLICT, $exception->getMessage());
        }
    }

    public function update(UpdateConfiguracionRespaldoRequest $request, UpdateConfiguracionRespaldoAction $action): ConfiguracionRespaldoResource
    {
        $this->authorizePermission($request->user(), 'configuracion_respaldos.actualizar');

        try {
            return new ConfiguracionRespaldoResource($action->execute($request->user(), $request->validated()));
        } catch (MultipleBackupConfigurationsException $exception) {
            abort(Response::HTTP_CONFLICT, $exception->getMessage());
        }
    }

    private function authorizePermission(?User $user, string $permission): void
    {
        abort_unless(
            $user !== null
                && $user->hasRole('Administrador')
                && $user->can($permission),
            Response::HTTP_FORBIDDEN
        );
    }
}
