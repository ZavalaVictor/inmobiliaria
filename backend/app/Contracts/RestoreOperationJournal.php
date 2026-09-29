<?php

namespace App\Contracts;

interface RestoreOperationJournal
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function append(string $event, array $context): void;
}
