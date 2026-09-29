<?php

namespace Tests\Fakes;

use App\Contracts\BackupOperationLock;
use App\Exceptions\BackupLockUnavailableException;

class FakeBackupOperationLock implements BackupOperationLock
{
    public bool $occupied = false;

    public function execute(callable $callback): mixed
    {
        if ($this->occupied) {
            throw new BackupLockUnavailableException('fake lock occupied');
        }

        return $callback();
    }
}
