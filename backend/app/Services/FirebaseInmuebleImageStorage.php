<?php

namespace App\Services;

use App\Contracts\InmuebleImageStorage;
use Google\Cloud\Core\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\UploadedFile;
use LogicException;

class FirebaseInmuebleImageStorage implements InmuebleImageStorage
{
    /** @var array<string, mixed> */
    private array $configuration;

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function __construct(?array $configuration = null)
    {
        $this->configuration = $configuration ?? config('services.firebase', []);
    }

    public function upload(UploadedFile $file, string $path, string $mimeType): ?string
    {
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new \RuntimeException('Unable to open uploaded image stream.');
        }

        try {
            $this->bucket()->upload(
                $stream,
                [
                    'name' => $path,
                    'metadata' => [
                        'contentType' => $mimeType,
                        'cacheControl' => 'public,max-age=31536000',
                    ],
                ]
            );
        } finally {
            if (is_resource($stream) && get_resource_type($stream) !== 'Unknown') {
                fclose($stream);
            }
        }

        return $this->publicUrl($path);
    }

    public function delete(string $path): void
    {
        try {
            $this->bucket()->object($path)->delete();
        } catch (NotFoundException) {
            // Deleting an already missing object is intentionally idempotent.
        }
    }

    private function bucket(): Bucket
    {
        $bucketName = (string) ($this->configuration['images_bucket'] ?? '');

        if ($bucketName === '') {
            throw new LogicException('Firebase images bucket is not configured.');
        }

        $options = array_filter([
            'projectId' => $this->configuration['project_id'] ?? null,
            'keyFilePath' => $this->configuration['credentials'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return (new StorageClient($options))->bucket($bucketName);
    }

    private function publicUrl(string $path): ?string
    {
        $baseUrl = rtrim((string) ($this->configuration['images_public_url_base'] ?? ''), '/');

        if ($baseUrl === '') {
            return null;
        }

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $baseUrl.'/'.rawurlencode((string) $this->configuration['images_bucket']).'/'.$encodedPath;
    }
}
