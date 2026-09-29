<?php

namespace Tests\Fakes;

use App\Contracts\BackupProcessRunner;
use RuntimeException;

class FakeBackupProcessRunner implements BackupProcessRunner
{
    /** @var list<string> */
    public array $command = [];

    public ?string $inputPath = null;

    public bool $shouldFail = false;

    public function run(array $command, ?string $inputPath = null): void
    {
        $this->command = $command;
        $this->inputPath = $inputPath;

        if ($this->shouldFail) {
            throw new RuntimeException('fake process failed');
        }
    }
}
