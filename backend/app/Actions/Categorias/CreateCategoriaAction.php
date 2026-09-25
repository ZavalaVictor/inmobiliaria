<?php

namespace App\Actions\Categorias;

use App\Models\Categoria;
use Illuminate\Support\Arr;

final class CreateCategoriaAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Categoria
    {
        return Categoria::create(Arr::only($attributes, [
            'nombre',
            'descripcion',
            'activo',
        ]));
    }
}
