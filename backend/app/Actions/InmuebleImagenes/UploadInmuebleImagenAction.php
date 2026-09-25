<?php

namespace App\Actions\InmuebleImagenes;

use App\Contracts\InmuebleImageStorage;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UploadInmuebleImagenAction
{
    public function __construct(private readonly InmuebleImageStorage $storage) {}

    public function execute(Inmueble $inmueble, UploadedFile $file, ?string $textoAlternativo = null): InmuebleImagen
    {
        $mimeType = (string) $file->getMimeType();
        $extension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ][$mimeType] ?? null;

        if ($extension === null) {
            throw new RuntimeException('Unsupported image MIME type.');
        }

        $path = 'inmuebles/'.$inmueble->getKey().'/'.Str::uuid().'.'.$extension;
        try {
            $url = $this->storage->upload($file, $path, $mimeType);
        } catch (Throwable $exception) {
            $this->compensateRemoteUpload($inmueble, $path);

            throw $exception;
        }

        try {
            return DB::transaction(function () use ($inmueble, $file, $mimeType, $path, $url, $textoAlternativo): InmuebleImagen {
                $lockedInmueble = Inmueble::query()
                    ->whereKey($inmueble->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $activeImages = $lockedInmueble->imagenes();
                $isFirst = ! $activeImages->exists();
                $nextOrder = ((int) ($activeImages->max('orden') ?? -1)) + 1;

                return InmuebleImagen::create([
                    'inmueble_id' => $lockedInmueble->getKey(),
                    'firebase_path' => $path,
                    'url_publica' => $url,
                    'nombre_original' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type' => $mimeType,
                    'tamano_bytes' => $file->getSize(),
                    'es_principal' => $isFirst,
                    'orden' => $nextOrder,
                    'texto_alternativo' => $textoAlternativo,
                ]);
            });
        } catch (Throwable $exception) {
            $this->compensateRemoteUpload($inmueble, $path);

            throw $exception;
        }
    }

    private function compensateRemoteUpload(Inmueble $inmueble, string $path): void
    {
        try {
            $this->storage->delete($path);
        } catch (Throwable $compensationException) {
            Log::error('Inmueble image upload compensation failed.', [
                'inmueble_id' => $inmueble->getKey(),
                'firebase_path' => $path,
                'error_type' => $compensationException::class,
            ]);
        }
    }
}
