<?php

namespace App\Services;

use App\Exceptions\BackupServiceException;
use Illuminate\Support\Str;

class DatabaseCredentialsFile
{
    public function create(): string
    {
        $directory = storage_path('app/private/backups/runtime');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new BackupServiceException('No fue posible preparar las credenciales temporales.');
        }

        $path = $directory.'/db-'.Str::uuid().'.cnf';
        $connection = config('database.default');
        $database = config("database.connections.$connection", []);
        $contents = "[client]\n"
            .'host='.($database['host'] ?? '')."\n"
            .'port='.($database['port'] ?? '')."\n"
            .'user='.($database['username'] ?? '')."\n"
            .'password='.($database['password'] ?? '')."\n";

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new BackupServiceException('No fue posible crear las credenciales temporales.');
        }

        chmod($path, 0600);

        return $path;
    }
}
