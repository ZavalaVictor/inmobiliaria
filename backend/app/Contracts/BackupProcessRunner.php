<?php

namespace App\Contracts;

interface BackupProcessRunner
{
    /**
     * @param  list<string>  $command
     */
    public function run(array $command, ?string $inputPath = null): void;
}
