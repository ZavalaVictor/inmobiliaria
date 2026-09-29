<?php

namespace App\Contracts;

interface DatabaseBackupService
{
    public function dumpTo(string $destinationPath): void;
}
