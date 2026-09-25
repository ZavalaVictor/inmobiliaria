<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface InmuebleImageStorage
{
    public function upload(UploadedFile $file, string $path, string $mimeType): ?string;

    public function delete(string $path): void;
}
