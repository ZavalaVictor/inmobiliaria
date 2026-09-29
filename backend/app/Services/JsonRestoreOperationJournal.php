<?php

namespace App\Services;

use App\Contracts\RestoreOperationJournal;
use App\Exceptions\BackupServiceException;

class JsonRestoreOperationJournal implements RestoreOperationJournal
{
    public function append(string $event, array $context): void
    {
        $path = (string) config('backup.journal_path');
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new BackupServiceException('No fue posible preparar el journal de restauración.');
        }

        $safe = [
            'timestamp' => now()->toISOString(),
            'evento' => $event,
            'respaldo_id' => $context['respaldo_id'] ?? null,
            'nombre_archivo' => $context['nombre_archivo'] ?? null,
            'checksum' => $context['checksum'] ?? null,
            'actor_user_id' => $context['actor_user_id'] ?? null,
            'resultado' => $context['resultado'] ?? null,
        ];

        $handle = fopen($path, 'ab');
        if ($handle === false) {
            throw new BackupServiceException('No fue posible abrir el journal de restauración.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new BackupServiceException('No fue posible bloquear el journal de restauración.');
            }

            fwrite($handle, json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
