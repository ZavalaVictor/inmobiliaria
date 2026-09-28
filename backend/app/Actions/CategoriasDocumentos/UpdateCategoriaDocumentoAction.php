<?php

namespace App\Actions\CategoriasDocumentos;

use App\Models\CategoriaDocumento;
use Illuminate\Support\Arr;

final class UpdateCategoriaDocumentoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(CategoriaDocumento $categoria, array $attributes): CategoriaDocumento
    {
        $categoria->fill(Arr::only($attributes, [
            'nombre',
            'descripcion',
            'activo',
        ]));
        $categoria->save();

        return $categoria->fresh();
    }
}
