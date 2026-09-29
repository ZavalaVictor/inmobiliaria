<?php

namespace Tests\Fakes;

use App\Contracts\DatabaseBackupService;
use RuntimeException;

class FakeDatabaseBackupService implements DatabaseBackupService
{
    public bool $shouldFail = false;

    public int $calls = 0;

    public function dumpTo(string $destinationPath): void
    {
        $this->calls++;
        if ($this->shouldFail) {
            throw new RuntimeException('fake dump failed');
        }

        file_put_contents($destinationPath, "-- fake dump\nCREATE TABLE fake (id INT);\n");
    }
}
