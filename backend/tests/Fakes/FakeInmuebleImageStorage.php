<?php

namespace Tests\Fakes;

use App\Contracts\InmuebleImageStorage;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

class FakeInmuebleImageStorage implements InmuebleImageStorage
{
    /** @var array<int, array{path: string, mime_type: string, original_name: string}> */
    public array $uploads = [];

    /** @var array<int, string> */
    public array $deletes = [];

    public bool $failUpload = false;

    public bool $failUploadAfterRecording = false;

    public ?Throwable $uploadException = null;

    public bool $failDelete = false;

    /** @var array<int, string> */
    public array $missingPaths = [];

    public function upload(UploadedFile $file, string $path, string $mimeType): ?string
    {
        if ($this->failUpload) {
            throw new RuntimeException('Fake upload failure.');
        }

        $this->uploads[] = [
            'path' => $path,
            'mime_type' => $mimeType,
            'original_name' => $file->getClientOriginalName(),
        ];

        if ($this->failUploadAfterRecording) {
            throw $this->uploadException ?? new RuntimeException('Fake upload failure after recording.');
        }

        return 'https://images.test/'.rawurlencode($path);
    }

    public function delete(string $path): void
    {
        $this->deletes[] = $path;

        if (in_array($path, $this->missingPaths, true)) {
            return;
        }

        if ($this->failDelete) {
            throw new RuntimeException('Fake delete failure.');
        }
    }
}
