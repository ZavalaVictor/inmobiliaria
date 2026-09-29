<?php

namespace App\Contracts;

interface BackupOperationLock
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function execute(callable $callback): mixed;
}
