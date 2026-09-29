<?php

namespace App\Services;

use App\Contracts\BackupProcessRunner;
use App\Exceptions\BackupServiceException;
use Symfony\Component\Process\Process;

class SymfonyBackupProcessRunner implements BackupProcessRunner
{
    public function run(array $command, ?string $inputPath = null): void
    {
        $process = new Process($command);
        $process->setTimeout((int) config('backup.process_timeout', 3600));

        if ($inputPath !== null) {
            $input = fopen($inputPath, 'rb');
            if ($input === false) {
                throw new BackupServiceException('No fue posible abrir el archivo de entrada.');
            }

            $process->setInput($input);
        }

        try {
            $process->run();
        } finally {
            if (isset($input) && is_resource($input)) {
                fclose($input);
            }
        }

        if (! $process->isSuccessful()) {
            throw new BackupServiceException('El proceso de respaldo terminó con error.');
        }
    }
}
