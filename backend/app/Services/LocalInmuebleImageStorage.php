<?php

namespace App\Services;

use App\Contracts\InmuebleImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LocalInmuebleImageStorage implements InmuebleImageStorage
{
    public function upload(UploadedFile $file, string $path, string $mimeType): ?string
    {
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to open uploaded image stream.');
        }

        try {
            $stored = Storage::disk('public')->put($path, $stream, ['visibility' => 'public']);
        } finally {
            if (is_resource($stream) && get_resource_type($stream) !== 'Unknown') {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw new RuntimeException('Unable to store uploaded image locally.');
        }

        return Storage::disk('public')->url($path);
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
