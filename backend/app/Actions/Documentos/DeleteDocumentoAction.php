<?php

namespace App\Actions\Documentos;

use App\Contracts\DocumentoPrivateStorage;
use App\Models\Documento;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteDocumentoAction
{
    public function __construct(private readonly DocumentoPrivateStorage $storage) {}

    public function execute(Documento $documento): void
    {
        $path = $documento->firebase_path;
        $documento->delete();

        try {
            $this->storage->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Private document remote cleanup pending.', [
                'documento_id' => $documento->getKey(),
                'error_type' => $exception::class,
            ]);
        }
    }
}
