<?php

namespace App\Actions\Respaldos;

use App\Enums\TipoRespaldo;
use App\Jobs\GenerateRespaldoJob;
use App\Models\Respaldo;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRespaldoAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    public function execute(?User $actor, TipoRespaldo $type = TipoRespaldo::Manual): Respaldo
    {
        $respaldo = DB::transaction(function () use ($actor, $type): Respaldo {
            $filename = 'backup_'.now()->format('Ymd_His').'_'.Str::uuid().'.sql';
            $respaldo = Respaldo::create([
                'generado_por_user_id' => $actor?->getKey(),
                'tipo' => $type,
                'estado' => 'pendiente',
                'nombre_archivo' => $filename,
                'ruta_archivo' => now()->format('Y/m').'/'.$filename,
            ]);

            $this->bitacora->record(
                $actor,
                'respaldo_creado',
                'respaldo',
                $respaldo->getKey(),
                'Respaldo creado.',
                null,
                [
                    'tipo' => $respaldo->tipo,
                    'estado' => $respaldo->estado,
                ],
            );

            return $respaldo;
        });

        DB::afterCommit(fn (): mixed => GenerateRespaldoJob::dispatch($respaldo->getKey()));

        return $respaldo->fresh(['generadoPor:id,nombres,apellido_paterno,apellido_materno']);
    }
}
