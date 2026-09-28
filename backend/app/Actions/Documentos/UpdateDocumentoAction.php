<?php

namespace App\Actions\Documentos;

use App\Models\Documento;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateDocumentoAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Documento $documento, array $attributes, ?User $actor = null): Documento
    {
        DB::transaction(function () use ($documento, $attributes, $actor): void {
            $before = $this->snapshot($documento);
            $documento->fill(Arr::only($attributes, [
                'categoria_documento_id',
                'fecha_documento',
                'fecha_vencimiento',
                'observaciones',
            ]));
            $documento->save();
            $this->bitacora->record($actor, 'documento_actualizado', 'documento', $documento->getKey(), 'Documento actualizado.', $before, $this->snapshot($documento));
        });

        return $documento->fresh();
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
