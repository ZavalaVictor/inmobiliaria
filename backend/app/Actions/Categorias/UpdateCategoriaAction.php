<?php

namespace App\Actions\Categorias;

use App\Models\Categoria;
use Illuminate\Support\Arr;

final class UpdateCategoriaAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Categoria $categoria, array $attributes): Categoria
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
