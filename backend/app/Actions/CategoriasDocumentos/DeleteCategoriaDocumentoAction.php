<?php

namespace App\Actions\CategoriasDocumentos;

use App\Models\CategoriaDocumento;
use Illuminate\Validation\ValidationException;

final class DeleteCategoriaDocumentoAction
{
    public function execute(CategoriaDocumento $categoria): void
    {
        if ($categoria->documentos()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => ['La categoría no puede eliminarse porque tiene documentos asociados.'],
            ]);
        }

        $categoria->delete();
    }
}
