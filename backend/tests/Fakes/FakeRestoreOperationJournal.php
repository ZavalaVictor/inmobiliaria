<?php

namespace Tests\Fakes;

use App\Contracts\RestoreOperationJournal;

class FakeRestoreOperationJournal implements RestoreOperationJournal
{
    /** @var list<array<string, mixed>> */
    public array $entries = [];

    public function append(string $event, array $context): void
    {
        $this->entries[] = ['evento' => $event] + $context;
    }
}
