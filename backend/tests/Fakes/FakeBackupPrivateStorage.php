<?php

namespace Tests\Fakes;

use App\Contracts\BackupPrivateStorage;
use RuntimeException;

class FakeBackupPrivateStorage implements BackupPrivateStorage
{
    /** @var array<string, string> */
    public array $files = [];

    /** @var list<string> */
    public array $deleted = [];

    public bool $failOnPut = false;

    public function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fake-backup-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear temp fake.');
        }

        return $path;
    }

    public function putFromPath(string $sourcePath, string $key): void
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('Temp fake inexistente.');
        }

        $this->files[$key] = (string) file_get_contents($sourcePath);
        unlink($sourcePath);

        if ($this->failOnPut) {
            throw new RuntimeException('fake storage put failed');
        }
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    public function readStream(string $key)
    {
        $path = tempnam(sys_get_temp_dir(), 'fake-read-');
        if ($path === false) {
            throw new RuntimeException('No se pudo preparar stream fake.');
        }
        file_put_contents($path, $this->files[$key] ?? '');
        $stream = fopen($path, 'rb');
        unlink($path);

        return $stream;
    }

    public function size(string $key): int
    {
        return strlen($this->files[$key] ?? '');
    }

    public function checksum(string $key): string
    {
        return hash('sha256', $this->files[$key] ?? '');
    }

    public function delete(string $key): void
    {
        $this->deleted[] = $key;
        unset($this->files[$key]);
    }

    public function absolutePath(string $key): string
    {
        return '/fake/private/backups/'.$key;
    }

    public function put(string $key, string $contents): void
    {
        $this->files[$key] = $contents;
    }
}
