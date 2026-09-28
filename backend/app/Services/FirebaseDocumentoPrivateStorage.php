<?php

namespace App\Services;

use App\Contracts\DocumentoPrivateStorage;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\UploadedFile;
use LogicException;

final class FirebaseDocumentoPrivateStorage implements DocumentoPrivateStorage
{
    /** @var array<string, mixed> */
    private array $configuration;

    /**
     * @param  array<string, mixed>|null  $configuration
     */
    public function __construct(?array $configuration = null)
    {
        $this->configuration = $configuration ?? config('services.firebase', []);
    }

    public function upload(UploadedFile $file, string $path, string $mimeType): void
    {
        $stream = fopen($file->getRealPath(), 'rb');

        try {
            $this->bucket()->upload($stream, [
                'name' => $path,
                'metadata' => [
                    'contentType' => $mimeType,
                ],
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function stream(string $path, callable $write): void
    {
        $stream = $this->bucket()->object($path)->downloadAsStream();

        while (! $stream->eof()) {
            $chunk = $stream->read(8192);

            if ($chunk !== '') {
                $write($chunk);
            }
        }
    }

    public function size(string $path): ?int
    {
        $size = $this->bucket()->object($path)->info()['size'] ?? null;

        return $size === null ? null : (int) $size;
    }

    public function delete(string $path): void
    {
        try {
            $this->bucket()->object($path)->delete();
        } catch (NotFoundException) {
            // Deleting a missing private object is intentionally idempotent.
        }
    }

    private function bucket(): Bucket
    {
        $bucketName = (string) ($this->configuration['documents_bucket'] ?? '');

        if ($bucketName === '') {
            throw new LogicException('Firebase documents bucket is not configured.');
        }

        $options = array_filter([
            'projectId' => $this->configuration['project_id'] ?? null,
            'keyFilePath' => $this->configuration['credentials'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return (new StorageClient($options))->bucket($bucketName);
    }
}
