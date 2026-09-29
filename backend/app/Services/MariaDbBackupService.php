<?php

namespace App\Services;

use App\Contracts\BackupProcessRunner;
use App\Contracts\DatabaseBackupService;
use App\Exceptions\BackupServiceException;

class MariaDbBackupService implements DatabaseBackupService
{
    public function __construct(
        private readonly BackupProcessRunner $runner,
        private readonly DatabaseCredentialsFile $credentials,
    ) {}

    public function dumpTo(string $destinationPath): void
    {
        $this->assertNotTesting();
        $credentialsPath = $this->credentials->create();

        try {
            $database = config('database.connections.'.config('database.default').'.database');
            if (! is_string($database) || $database === '') {
                throw new BackupServiceException('La base de datos configurada no es válida.');
            }

            $this->runner->run([
                (string) config('backup.dump_binary', 'mariadb-dump'),
                '--defaults-extra-file='.$credentialsPath,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--events',
                '--no-create-db',
                '--skip-add-drop-database',
                '--result-file='.$destinationPath,
                $database,
            ]);
        } finally {
            @unlink($credentialsPath);
        }
    }

    private function assertNotTesting(): void
    {
        if (config('app.env') === 'testing') {
            throw new BackupServiceException('La ejecución de procesos de respaldo está deshabilitada en testing.');
        }
    }
}
