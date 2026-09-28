<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface DocumentoPrivateStorage
{
    public function upload(UploadedFile $file, string $path, string $mimeType): void;

    public function stream(string $path, callable $write): void;

    public function size(string $path): ?int;

    public function delete(string $path): void;
}
