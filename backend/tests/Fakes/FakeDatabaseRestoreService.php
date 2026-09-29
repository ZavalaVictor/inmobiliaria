<?php

namespace Tests\Fakes;

use App\Contracts\DatabaseRestoreService;
use RuntimeException;

class FakeDatabaseRestoreService implements DatabaseRestoreService
{
    public bool $shouldFail = false;

    /** @var callable|null */
    public $duringRestore;

    public ?string $sourcePath = null;

    public function restoreFrom(string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
        if (is_callable($this->duringRestore)) {
            ($this->duringRestore)();
        }
        if ($this->shouldFail) {
            throw new RuntimeException('fake restore failed');
        }
    }
}
