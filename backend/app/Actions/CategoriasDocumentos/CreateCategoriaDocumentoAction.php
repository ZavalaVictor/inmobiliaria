<?php

namespace App\Actions\CategoriasDocumentos;

use App\Models\CategoriaDocumento;
use Illuminate\Support\Arr;

final class CreateCategoriaDocumentoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): CategoriaDocumento
    {
        return CategoriaDocumento::query()->create(Arr::only($attributes, [
            'nombre',
            'descripcion',
            'activo',
        ]))->fresh();
    }
}
