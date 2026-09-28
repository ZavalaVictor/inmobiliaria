<?php

namespace App\Actions\Operaciones;

use App\Models\Operacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteOperacionAction
{
    public function execute(Operacion $operacion): void
    {
        DB::transaction(function () use ($operacion): void {
            $locked = Operacion::query()->whereKey($operacion->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->asignacionesAgentes()->exists()) {
                throw ValidationException::withMessages(['operacion' => ['No se puede eliminar una Operación con Agentes asociados.']]);
            }

            if ($locked->documentos()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['operacion' => ['No se puede eliminar una Operación con documentos asociados.']]);
            }

            $locked->delete();
        });
    }
}
