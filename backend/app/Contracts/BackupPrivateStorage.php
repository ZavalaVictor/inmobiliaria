<?php

namespace App\Contracts;

interface BackupPrivateStorage
{
    public function temporaryPath(): string;

    public function putFromPath(string $sourcePath, string $key): void;

    public function exists(string $key): bool;

    /** @return resource */
    public function readStream(string $key);

    public function size(string $key): int;

    public function checksum(string $key): string;

    public function delete(string $key): void;

    public function absolutePath(string $key): string;
}
