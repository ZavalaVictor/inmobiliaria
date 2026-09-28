<?php

namespace App\Actions\Documentos;

use App\Models\Documento;
use Illuminate\Support\Arr;

final class UpdateDocumentoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Documento $documento, array $attributes): Documento
    {
        $documento->fill(Arr::only($attributes, [
            'categoria_documento_id',
            'fecha_documento',
            'fecha_vencimiento',
            'observaciones',
        ]));
        $documento->save();

        return $documento->fresh();
    }
}
