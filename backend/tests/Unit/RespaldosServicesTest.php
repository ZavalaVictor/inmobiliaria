<?php

namespace Tests\Unit;

use App\Exceptions\BackupServiceException;
use App\Services\JsonRestoreOperationJournal;
use App\Services\LocalBackupPrivateStorage;
use App\Services\MariaDbBackupService;
use App\Services\MariaDbRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeBackupProcessRunner;
use Tests\TestCase;

class RespaldosServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_is_argument_based_and_hides_password(): void
    {
        $runner = new FakeBackupProcessRunner;
        config([
            'app.env' => 'local',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'sotytech_bd_test',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => 3306,
            'database.connections.mysql.username' => 'tester',
            'database.connections.mysql.password' => 'secret-password',
        ]);
        $service = app(MariaDbBackupService::class, ['runner' => $runner]);
        $path = tempnam(sys_get_temp_dir(), 'backup-command-');
        $service->dumpTo($path);

        self::assertSame('mariadb-dump', $runner->command[0]);
        self::assertNotContains('secret-password', $runner->command);
        self::assertNotContains('--databases', $runner->command);
        self::assertNotContains('--add-drop-database', $runner->command);
        $options = (string) collect($runner->command)->first(fn (string $argument): bool => str_starts_with($argument, '--defaults-extra-file='));
        self::assertFileDoesNotExist(substr($options, strlen('--defaults-extra-file=')));
        @unlink($path);
    }

    public function test_real_backup_service_refuses_to_execute_in_testing(): void
    {
        config(['app.env' => 'testing']);
        $runner = new FakeBackupProcessRunner;
        $this->expectException(BackupServiceException::class);
        app(MariaDbBackupService::class, ['runner' => $runner])->dumpTo('/tmp/never-executed.sql');
        self::assertSame([], $runner->command);
    }

    public function test_real_restore_service_refuses_to_execute_in_testing(): void
    {
        config(['app.env' => 'testing', 'backup.restore_enabled' => true]);
        $runner = new FakeBackupProcessRunner;

        $this->expectException(BackupServiceException::class);
        app(MariaDbRestoreService::class, ['runner' => $runner])->restoreFrom('/tmp/never-executed.sql');
        self::assertSame([], $runner->command);
    }

    public function test_restore_journal_is_jsonl_and_allowlisted(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'restore-journal-');
        @unlink($path);
        config(['backup.journal_path' => $path]);

        $journal = app(JsonRestoreOperationJournal::class);
        $journal->append('restore_started', [
            'respaldo_id' => 8,
            'nombre_archivo' => 'backup.sql',
            'checksum' => str_repeat('a', 64),
            'actor_user_id' => 3,
            'password' => 'must-not-be-written',
            'credentials' => 'must-not-be-written',
            'command' => 'mariadb --password=secret',
            'absolute_path' => '/private/secret.sql',
        ]);

        $line = trim((string) file_get_contents($path));
        $entry = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('restore_started', $entry['evento']);
        self::assertArrayNotHasKey('password', $entry);
        self::assertArrayNotHasKey('credentials', $entry);
        self::assertArrayNotHasKey('command', $entry);
        self::assertArrayNotHasKey('absolute_path', $entry);
        @unlink($path);
    }

    public function test_options_file_is_removed_when_process_fails_and_keys_stay_private(): void
    {
        config([
            'app.env' => 'local',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'sotytech_bd_test',
            'database.connections.mysql.password' => 'secret-password',
        ]);
        $runner = new FakeBackupProcessRunner;
        $runner->shouldFail = true;
        $service = app(MariaDbBackupService::class, ['runner' => $runner]);
        $path = tempnam(sys_get_temp_dir(), 'backup-failure-');

        try {
            $service->dumpTo($path);
            self::fail('Se esperaba una excepción del proceso.');
        } catch (\RuntimeException $exception) {
            self::assertStringNotContainsString('secret-password', $exception->getMessage());
        }

        $options = (string) collect($runner->command)->first(fn (string $argument): bool => str_starts_with($argument, '--defaults-extra-file='));
        self::assertFileDoesNotExist(substr($options, strlen('--defaults-extra-file=')));
        @unlink($path);
    }

    public function test_private_storage_rejects_traversal_and_absolute_keys(): void
    {
        $storage = app(LocalBackupPrivateStorage::class);

        foreach ([
            '../outside.sql',
            '/absolute/path.sql',
            '..\\outside.sql',
            "safe\0key.sql",
        ] as $key) {
            try {
                $storage->absolutePath($key);
                self::fail('La key insegura fue aceptada: '.var_export($key, true));
            } catch (\RuntimeException) {
                self::assertTrue(true);
            }
        }

        self::assertStringStartsWith(
            storage_path('app/private/backups'),
            $storage->absolutePath('2026/09/backup.sql'),
        );
    }

    public function test_restore_options_file_is_removed_when_process_fails(): void
    {
        config([
            'app.env' => 'local',
            'backup.restore_enabled' => true,
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'sotytech_bd_test',
        ]);
        $runner = new FakeBackupProcessRunner;
        $runner->shouldFail = true;
        $service = app(MariaDbRestoreService::class, ['runner' => $runner]);
        $source = tempnam(sys_get_temp_dir(), 'restore-failure-');

        try {
            $service->restoreFrom($source);
            self::fail('Se esperaba una excepción del proceso.');
        } catch (\RuntimeException) {
            self::assertTrue(true);
        }

        $options = (string) collect($runner->command)->first(fn (string $argument): bool => str_starts_with($argument, '--defaults-extra-file='));
        self::assertFileDoesNotExist(substr($options, strlen('--defaults-extra-file=')));
        @unlink($source);
    }
}
