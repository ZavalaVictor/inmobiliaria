<?php

namespace App\Actions\InmuebleImagenes;

use App\Contracts\InmuebleImageStorage;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteInmuebleImagenAction
{
    public function __construct(private readonly InmuebleImageStorage $storage) {}

    public function execute(Inmueble $inmueble, InmuebleImagen $imagen): void
    {
        $deleted = DB::transaction(function () use ($inmueble, $imagen): array {
            $lockedInmueble = Inmueble::query()
                ->whereKey($inmueble->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $target = InmuebleImagen::query()
                ->whereKey($imagen->getKey())
                ->where('inmueble_id', $lockedInmueble->getKey())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->firstOrFail();

            $wasPrincipal = (bool) $target->es_principal;
            $path = $target->firebase_path;
            $target->delete();

            if ($wasPrincipal) {
                $next = InmuebleImagen::query()
                    ->where('inmueble_id', $lockedInmueble->getKey())
                    ->whereNull('deleted_at')
                    ->orderBy('orden')
                    ->orderBy('id')
                    ->first();

                $next?->update(['es_principal' => true]);
            }

            return [
                'id' => $target->getKey(),
                'path' => $path,
            ];
        });

        try {
            $this->storage->delete($deleted['path']);
        } catch (Throwable $exception) {
            Log::warning('Inmueble image remote cleanup pending.', [
                'inmueble_id' => $inmueble->getKey(),
                'imagen_id' => $deleted['id'],
                'firebase_path' => $deleted['path'],
                'error_type' => $exception::class,
            ]);
        }
    }
}
