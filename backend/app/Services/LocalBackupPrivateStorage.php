<?php

namespace App\Services;

use App\Contracts\BackupPrivateStorage;
use App\Exceptions\BackupServiceException;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LocalBackupPrivateStorage implements BackupPrivateStorage
{
    public function temporaryPath(): string
    {
        $runtime = $this->absolutePath('runtime');
        if (! is_dir($runtime) && ! mkdir($runtime, 0700, true) && ! is_dir($runtime)) {
            throw new BackupServiceException('No fue posible preparar el storage privado.');
        }

        $path = tempnam($runtime, 'dump-');
        if ($path === false) {
            throw new BackupServiceException('No fue posible crear el archivo temporal.');
        }

        chmod($path, 0600);

        return $path;
    }

    public function putFromPath(string $sourcePath, string $key): void
    {
        $this->assertSafeKey($key);

        if (! is_file($sourcePath)) {
            throw new BackupServiceException('El archivo temporal no existe.');
        }

        $destination = $this->absolutePath($key);
        $directory = dirname($destination);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new BackupServiceException('No fue posible preparar el destino privado.');
        }

        if (! rename($sourcePath, $destination)) {
            throw new BackupServiceException('No fue posible mover el respaldo privado.');
        }

        chmod($destination, 0600);
    }

    public function exists(string $key): bool
    {
        return Storage::disk(config('backup.disk'))->exists($this->safeKey($key));
    }

    public function readStream(string $key)
    {
        $this->assertSafeKey($key);
        $stream = Storage::disk(config('backup.disk'))->readStream($key);

        if (! is_resource($stream)) {
            throw new BackupServiceException('No fue posible abrir el respaldo privado.');
        }

        return $stream;
    }

    public function size(string $key): int
    {
        $this->assertSafeKey($key);

        return (int) Storage::disk(config('backup.disk'))->size($key);
    }

    public function checksum(string $key): string
    {
        $path = $this->absolutePath($key);
        if (! is_file($path)) {
            throw new BackupServiceException('El respaldo privado no existe.');
        }

        $checksum = hash_file('sha256', $path);
        if ($checksum === false) {
            throw new BackupServiceException('No fue posible calcular la integridad del respaldo.');
        }

        return $checksum;
    }

    public function delete(string $key): void
    {
        $this->assertSafeKey($key);
        Storage::disk(config('backup.disk'))->delete($key);
    }

    public function absolutePath(string $key): string
    {
        $safeKey = $this->safeKey($key);

        return Storage::disk(config('backup.disk'))->path($safeKey);
    }

    private function safeKey(string $key): string
    {
        $this->assertSafeKey($key);

        return ltrim($key, '/');
    }

    private function assertSafeKey(string $key): void
    {
        if ($key === '' || str_contains($key, "\0") || str_contains($key, '..') || str_starts_with($key, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $key)) {
            throw new RuntimeException('La key de respaldo no es válida.');
        }

        if (str_contains($key, '\\')) {
            throw new RuntimeException('La key de respaldo no es válida.');
        }
    }
}
