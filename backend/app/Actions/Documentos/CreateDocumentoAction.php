<?php

namespace App\Actions\Documentos;

use App\Contracts\DocumentoPrivateStorage;
use App\Enums\MimeTypeDocumento;
use App\Models\CategoriaDocumento;
use App\Models\Documento;
use App\Models\User;
use App\Services\DocumentoDestinationAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateDocumentoAction
{
    public function __construct(
        private readonly DocumentoPrivateStorage $storage,
        private readonly DocumentoDestinationAccess $destinations,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $actor, UploadedFile $file, array $attributes): Documento
    {
        $category = CategoriaDocumento::query()->find($attributes['categoria_documento_id'] ?? null);

        if (! $category instanceof CategoriaDocumento || ! $category->activo) {
            throw ValidationException::withMessages([
                'categoria_documento_id' => ['La categoría no existe, está eliminada o está inactiva.'],
            ]);
        }

        $destination = $this->destinations->resolve($attributes);

        if (! $this->destinations->agentCanAccess($actor, $destination)) {
            abort(403);
        }

        $mimeType = MimeTypeDocumento::tryFrom((string) $file->getMimeType());

        if ($mimeType === null) {
            throw ValidationException::withMessages([
                'archivo' => ['El tipo MIME del archivo no está permitido.'],
            ]);
        }

        $extension = match ($mimeType) {
            MimeTypeDocumento::ApplicationPdf => 'pdf',
            MimeTypeDocumento::ImagePng => 'png',
            MimeTypeDocumento::ImageJpeg => 'jpg',
        };
        $pathSegment = [
            'propietario' => 'propietarios',
            'cliente' => 'clientes',
            'inmueble' => 'inmuebles',
            'operacion' => 'operaciones',
        ][$destination['type']];
        $path = 'documentos/'.$pathSegment.'/'.$destination['id'].'/'.Str::uuid().'.'.$extension;
        $originalName = $this->sanitizeOriginalName($file->getClientOriginalName());
        $metadata = [
            'categoria_documento_id' => $attributes['categoria_documento_id'],
            'subido_por_user_id' => $actor->getKey(),
            $destination['column'] => $destination['id'],
            'nombre_original' => $originalName,
            'firebase_path' => $path,
            'mime_type' => $mimeType->value,
            'tamano_bytes' => (int) $file->getSize(),
            'fecha_documento' => $attributes['fecha_documento'] ?? null,
            'fecha_vencimiento' => $attributes['fecha_vencimiento'] ?? null,
            'observaciones' => $attributes['observaciones'] ?? null,
        ];

        try {
            $this->storage->upload($file, $path, $mimeType->value);
        } catch (Throwable $exception) {
            $this->compensate($path, $destination['id']);

            throw $exception;
        }

        try {
            $documento = DB::transaction(fn (): Documento => Documento::create($metadata));
        } catch (Throwable $exception) {
            $this->compensate($path, $destination['id']);

            throw $exception;
        }

        return $documento->fresh();
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);

        return mb_substr($name !== '' ? $name : 'documento', 0, 255);
    }

    private function compensate(string $path, int $destinationId): void
    {
        try {
            $this->storage->delete($path);
        } catch (Throwable $compensationException) {
            Log::error('Private document upload compensation failed.', [
                'destination_id' => $destinationId,
                'error_type' => $compensationException::class,
            ]);
        }
    }
}
