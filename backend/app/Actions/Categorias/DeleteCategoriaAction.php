<?php

namespace App\Actions\Categorias;

use App\Models\Categoria;
use Illuminate\Validation\ValidationException;

final class DeleteCategoriaAction
{
    public function execute(Categoria $categoria): void
    {
        if ($categoria->inmuebles()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => 'La categoría no puede eliminarse porque tiene inmuebles asociados.',
            ]);
        }

        $categoria->delete();
    }
}
