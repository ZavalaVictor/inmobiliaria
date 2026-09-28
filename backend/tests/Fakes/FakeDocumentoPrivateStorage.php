<?php

namespace Tests\Fakes;

use App\Contracts\DocumentoPrivateStorage;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class FakeDocumentoPrivateStorage implements DocumentoPrivateStorage
{
    /** @var array<string, string> */
    public array $objects = [];

    /** @var array<int, array{path: string, mime_type: string, original_name: string}> */
    public array $uploads = [];

    /** @var array<int, string> */
    public array $deletes = [];

    public bool $failUpload = false;

    public bool $failUploadAfterRecording = false;

    public bool $failDelete = false;

    public function upload(UploadedFile $file, string $path, string $mimeType): void
    {
        if ($this->failUpload) {
            throw new RuntimeException('Fake private upload failure.');
        }

        $this->objects[$path] = (string) file_get_contents($file->getRealPath());
        $this->uploads[] = [
            'path' => $path,
            'mime_type' => $mimeType,
            'original_name' => $file->getClientOriginalName(),
        ];

        if ($this->failUploadAfterRecording) {
            throw new RuntimeException('Fake private upload failure after recording.');
        }
    }

    public function stream(string $path, callable $write): void
    {
        if (! array_key_exists($path, $this->objects)) {
            throw new RuntimeException('Fake private object not found.');
        }

        $write($this->objects[$path]);
    }

    public function size(string $path): ?int
    {
        return array_key_exists($path, $this->objects) ? strlen($this->objects[$path]) : null;
    }

    public function delete(string $path): void
    {
        $this->deletes[] = $path;

        if ($this->failDelete) {
            throw new RuntimeException('Fake private delete failure.');
        }

        unset($this->objects[$path]);
    }
}
