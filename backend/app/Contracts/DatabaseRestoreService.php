<?php

namespace App\Contracts;

interface DatabaseRestoreService
{
    public function restoreFrom(string $sourcePath): void;
}
