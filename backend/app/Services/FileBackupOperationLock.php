<?php

namespace App\Services;

use App\Contracts\BackupOperationLock;
use App\Exceptions\BackupLockUnavailableException;

class FileBackupOperationLock implements BackupOperationLock
{
    public function execute(callable $callback): mixed
    {
        $path = (string) config('backup.lock_path');
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new BackupLockUnavailableException('No fue posible preparar el lock operativo.');
        }

        $handle = fopen($path, 'c+');
        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            throw new BackupLockUnavailableException('Ya existe una operación de respaldo en curso.');
        }

        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
