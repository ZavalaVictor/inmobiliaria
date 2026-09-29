<?php

namespace App\Services;

use App\Contracts\BackupProcessRunner;
use App\Contracts\DatabaseRestoreService;
use App\Exceptions\BackupServiceException;

class MariaDbRestoreService implements DatabaseRestoreService
{
    public function __construct(
        private readonly BackupProcessRunner $runner,
        private readonly DatabaseCredentialsFile $credentials,
    ) {}

    public function restoreFrom(string $sourcePath): void
    {
        if (! (bool) config('backup.restore_enabled', false)) {
            throw new BackupServiceException('La restauración está deshabilitada.');
        }

        if (config('app.env') === 'testing') {
            throw new BackupServiceException('La ejecución de procesos de restauración está deshabilitada en testing.');
        }

        $database = config('database.connections.'.config('database.default').'.database');
        if (! is_string($database) || $database === '' || $database === 'sotytech_bd_v3') {
            throw new BackupServiceException('La base de datos objetivo no está autorizada.');
        }

        $credentialsPath = $this->credentials->create();

        try {
            $this->runner->run([
                (string) config('backup.restore_binary', 'mariadb'),
                '--defaults-extra-file='.$credentialsPath,
                $database,
            ], $sourcePath);
        } finally {
            @unlink($credentialsPath);
        }
    }
}
