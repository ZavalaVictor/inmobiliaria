<?php

return [
    'disk' => env('BACKUP_DISK', 'backups_private'),
    'dump_binary' => env('BACKUP_DUMP_BINARY', 'mariadb-dump'),
    'restore_binary' => env('BACKUP_RESTORE_BINARY', 'mariadb'),
    'process_timeout' => (int) env('BACKUP_PROCESS_TIMEOUT', 3600),
    'restore_enabled' => filter_var(env('BACKUP_RESTORE_ENABLED', false), FILTER_VALIDATE_BOOL),
    'lock_path' => storage_path('app/private/backups/runtime/operations.lock'),
    'journal_path' => storage_path('app/private/backups/runtime/restore-journal.jsonl'),
];
