<?php

namespace App\Actions\Documentos;

use App\Contracts\DocumentoPrivateStorage;
use App\Models\Documento;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeleteDocumentoAction
{
    public function __construct(
        private readonly DocumentoPrivateStorage $storage,
        private readonly BitacoraService $bitacora,
    ) {}

    public function execute(Documento $documento, ?User $actor = null): void
    {
        $path = $documento->firebase_path;
        $before = $this->snapshot($documento);
        DB::transaction(function () use ($documento, $actor, $before): void {
            $documento->delete();
            $this->bitacora->record($actor, 'documento_eliminado', 'documento', $documento->getKey(), 'Documento eliminado.', $before);
        });

        try {
            $this->storage->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Private document remote cleanup pending.', [
                'documento_id' => $documento->getKey(),
                'error_type' => $exception::class,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Documento $documento): array
    {
        return [
            'categoria_documento_id' => $documento->categoria_documento_id,
            'propietario_id' => $documento->propietario_id,
            'cliente_id' => $documento->cliente_id,
            'inmueble_id' => $documento->inmueble_id,
            'operacion_id' => $documento->operacion_id,
            'nombre_original' => $documento->nombre_original,
            'mime_type' => $documento->mime_type,
            'tamano_bytes' => $documento->tamano_bytes,
            'fecha_documento' => $documento->fecha_documento,
            'fecha_vencimiento' => $documento->fecha_vencimiento,
        ];
    }
}
