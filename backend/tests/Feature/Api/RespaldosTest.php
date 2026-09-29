<?php

namespace Tests\Feature\Api;

use App\Actions\Respaldos\RestoreRespaldoAction;
use App\Contracts\BackupOperationLock;
use App\Contracts\BackupPrivateStorage;
use App\Contracts\DatabaseBackupService;
use App\Contracts\DatabaseRestoreService;
use App\Contracts\RestoreOperationJournal;
use App\Enums\EstadoRespaldo;
use App\Exceptions\BackupRestoreException;
use App\Exceptions\BackupServiceException;
use App\Jobs\GenerateRespaldoJob;
use App\Models\Bitacora;
use App\Models\Respaldo;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\MariaDbBackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\Fakes\FakeBackupOperationLock;
use Tests\Fakes\FakeBackupPrivateStorage;
use Tests\Fakes\FakeBackupProcessRunner;
use Tests\Fakes\FakeDatabaseBackupService;
use Tests\Fakes\FakeDatabaseRestoreService;
use Tests\Fakes\FakeRestoreOperationJournal;
use Tests\TestCase;

class RespaldosTest extends TestCase
{
    use RefreshDatabase;

    private FakeBackupPrivateStorage $storage;

    private FakeDatabaseBackupService $backupService;

    private FakeDatabaseRestoreService $restoreService;

    private FakeBackupOperationLock $lock;

    private FakeRestoreOperationJournal $journal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->storage = new FakeBackupPrivateStorage;
        $this->backupService = new FakeDatabaseBackupService;
        $this->restoreService = new FakeDatabaseRestoreService;
        $this->lock = new FakeBackupOperationLock;
        $this->journal = new FakeRestoreOperationJournal;
        $this->app->instance(BackupPrivateStorage::class, $this->storage);
        $this->app->instance(DatabaseBackupService::class, $this->backupService);
        $this->app->instance(DatabaseRestoreService::class, $this->restoreService);
        $this->app->instance(BackupOperationLock::class, $this->lock);
        $this->app->instance(RestoreOperationJournal::class, $this->journal);
    }

    public function test_endpoints_are_admin_only_and_fail_closed(): void
    {
        $this->getJson('/api/v1/respaldos')->assertUnauthorized();
        $this->getJson('/api/v1/configuracion-respaldos')->assertUnauthorized();

        foreach (['Agente Inmobiliario', 'Asistente', 'Director General', 'Cliente'] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'@respaldos.test', $role);
            $this->apiGet('/api/v1/respaldos', $user)->assertForbidden();
            $this->apiGet('/api/v1/configuracion-respaldos', $user)->assertForbidden();
        }

        $unknown = $this->user('unknown@respaldos.test');
        $unknown->assignRole(Role::create(['name' => 'Rol inesperado', 'guard_name' => 'web']));
        $withoutRole = $this->user('without-role@respaldos.test');
        $admin = $this->user('without-permission@respaldos.test', 'Administrador');
        Role::findByName('Administrador', 'web')->revokePermissionTo(['respaldos.ver', 'configuracion_respaldos.ver']);

        foreach ([$unknown, $withoutRole, $admin] as $user) {
            $this->apiGet('/api/v1/respaldos', $user)->assertForbidden();
            $this->apiGet('/api/v1/configuracion-respaldos', $user)->assertForbidden();
        }
    }

    public function test_manual_backup_is_created_as_pending_and_dispatches_job(): void
    {
        Queue::fake();
        $admin = $this->user('backup-admin@example.test', 'Administrador');

        $response = $this->apiPost('/api/v1/respaldos', $admin, [])->assertAccepted();
        $respaldo = Respaldo::query()->findOrFail($response->json('data.id'));

        self::assertSame('manual', $respaldo->tipo->value);
        self::assertSame('pendiente', $respaldo->estado->value);
        self::assertSame($admin->id, $respaldo->generado_por_user_id);
        self::assertMatchesRegularExpression('/^backup_\d{8}_\d{6}_[0-9a-f-]{36}\.sql$/i', $respaldo->nombre_archivo);
        self::assertStringNotContainsString('..', $respaldo->ruta_archivo);
        self::assertStringNotContainsString('\\', $respaldo->ruta_archivo);
        $response->assertJsonMissingPath('data.ruta_archivo');
        $response->assertJsonMissingPath('data.checksum_sha256');
        Queue::assertPushed(GenerateRespaldoJob::class, fn (GenerateRespaldoJob $job): bool => $job->respaldoId === $respaldo->id);
        $this->assertDatabaseHas('bitacora', ['accion' => 'respaldo_creado', 'entidad_id' => $respaldo->id]);
    }

    public function test_create_rejects_any_payload(): void
    {
        $admin = $this->user('backup-payload@example.test', 'Administrador');
        foreach (['database', 'password', 'ruta_archivo', 'nombre_archivo', 'command', 'arguments', 'tipo'] as $field) {
            $this->apiPost('/api/v1/respaldos', $admin, [$field => 'forbidden'])->assertUnprocessable();
        }
        $this->apiPost('/api/v1/respaldos', $admin, ['unknown' => 'forbidden'])->assertUnprocessable();
        self::assertDatabaseCount('respaldos', 0);
    }

    public function test_mutations_require_their_specific_permissions(): void
    {
        $admin = $this->user('specific-permissions@example.test', 'Administrador');
        $role = Role::findByName('Administrador', 'web');

        $role->revokePermissionTo('respaldos.crear');
        $this->apiPost('/api/v1/respaldos', $admin, [])->assertForbidden();

        $respaldo = $this->completed();
        $role->revokePermissionTo('respaldos.restaurar');
        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, [
            'confirmacion' => 'RESTAURAR',
        ])->assertForbidden();
    }

    public function test_job_completes_or_marks_backup_failed_using_fakes(): void
    {
        $respaldo = $this->pending();
        (new GenerateRespaldoJob($respaldo->id))->handle($this->backupService, $this->storage, $this->lock, app(BitacoraService::class));
        $completed = $respaldo->fresh();
        self::assertSame(EstadoRespaldo::Completado, $completed->estado);
        self::assertGreaterThan(0, $completed->tamano_bytes);
        self::assertSame($this->storage->checksum($completed->ruta_archivo), $completed->checksum_sha256);
        self::assertNotNull($completed->fecha_finalizacion);
        $this->assertDatabaseHas('bitacora', ['accion' => 'respaldo_completado', 'entidad_id' => $completed->id]);

        (new GenerateRespaldoJob($completed->id))->handle($this->backupService, $this->storage, $this->lock, app(BitacoraService::class));
        self::assertSame(1, $this->backupService->calls);
        self::assertSame(1, Bitacora::query()->where('accion', 'respaldo_completado')->where('entidad_id', $completed->id)->count());

        $failed = $this->pending();
        $this->backupService->shouldFail = true;
        (new GenerateRespaldoJob($failed->id))->handle($this->backupService, $this->storage, $this->lock, app(BitacoraService::class));
        self::assertSame(EstadoRespaldo::Fallido, $failed->fresh()->estado);
        self::assertNull($failed->fresh()->checksum_sha256);
        self::assertArrayNotHasKey($failed->ruta_archivo, $this->storage->files);
        $this->assertDatabaseHas('bitacora', ['accion' => 'respaldo_fallido', 'entidad_id' => $failed->id]);
    }

    public function test_job_cleans_a_partial_final_file_when_storage_put_fails(): void
    {
        $respaldo = $this->pending();
        $this->storage->failOnPut = true;

        (new GenerateRespaldoJob($respaldo->id))->handle($this->backupService, $this->storage, $this->lock, app(BitacoraService::class));

        self::assertSame('fallido', $respaldo->fresh()->estado->value);
        self::assertArrayNotHasKey($respaldo->ruta_archivo, $this->storage->files);
        self::assertContains($respaldo->ruta_archivo, $this->storage->deleted);
    }

    public function test_job_leaves_pending_when_operational_lock_is_occupied(): void
    {
        $respaldo = $this->pending();
        $this->lock->occupied = true;
        (new GenerateRespaldoJob($respaldo->id))->handle($this->backupService, $this->storage, $this->lock, app(BitacoraService::class));
        self::assertSame(EstadoRespaldo::Pendiente, $respaldo->fresh()->estado);
    }

    public function test_download_requires_completed_valid_private_file(): void
    {
        $admin = $this->user('download-admin@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update(['checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo), 'tamano_bytes' => 11]);

        $response = $this->apiGet('/api/v1/respaldos/'.$respaldo->id.'/descargar', $admin);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/sql')
            ->assertHeader('Content-Disposition', 'attachment; filename='.$respaldo->nombre_archivo);

        $this->assertStringContainsString('SQL CONTENT', $response->streamedContent());

        $respaldo->update(['checksum_sha256' => str_repeat('a', 64)]);
        $this->apiGet('/api/v1/respaldos/'.$respaldo->id.'/descargar', $admin)->assertConflict();
        $respaldo->update(['estado' => 'pendiente']);
        $this->apiGet('/api/v1/respaldos/'.$respaldo->id.'/descargar', $admin)->assertNotFound();

        $respaldo->update(['estado' => 'completado', 'checksum_sha256' => hash('sha256', ''), 'tamano_bytes' => 0]);
        $this->storage->put($respaldo->ruta_archivo, '');
        $this->apiGet('/api/v1/respaldos/'.$respaldo->id.'/descargar', $admin)->assertNotFound();
    }

    public function test_restore_uses_fake_and_writes_journal_without_real_process(): void
    {
        config(['backup.restore_enabled' => true]);
        $admin = $this->user('restore-admin@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update(['checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo)]);

        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'RESTAURAR'])
            ->assertOk()
            ->assertJsonPath('data.estado_restauracion', 'completada');
        self::assertSame($admin->id, $respaldo->fresh()->restaurado_por_user_id);
        self::assertSame(['restore_started', 'restore_completed'], array_column($this->journal->entries, 'evento'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'restauracion_completada', 'entidad_id' => $respaldo->id]);
    }

    public function test_restore_reinstates_backup_metadata_after_database_rewind(): void
    {
        config(['backup.restore_enabled' => true]);
        $admin = $this->user('restore-rewind@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update([
            'checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo),
            'tamano_bytes' => 11,
            'fecha_inicio' => now()->subMinutes(2),
            'fecha_finalizacion' => now()->subMinute(),
        ]);
        $beforeRestore = $respaldo->fresh();
        $this->restoreService->duringRestore = function () use ($respaldo): void {
            Respaldo::query()->whereKey($respaldo->id)->update([
                'estado' => 'pendiente',
                'tamano_bytes' => null,
                'checksum_sha256' => null,
                'fecha_finalizacion' => null,
                'estado_restauracion' => null,
                'restaurado_por_user_id' => null,
                'restaurado_at' => null,
            ]);
        };

        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'RESTAURAR'])
            ->assertOk();

        $restored = $respaldo->fresh();
        self::assertSame('completado', $restored->estado->value);
        self::assertSame($beforeRestore->tipo, $restored->tipo);
        self::assertSame($beforeRestore->nombre_archivo, $restored->nombre_archivo);
        self::assertSame($beforeRestore->ruta_archivo, $restored->ruta_archivo);
        self::assertSame($beforeRestore->tamano_bytes, $restored->tamano_bytes);
        self::assertSame($beforeRestore->checksum_sha256, $restored->checksum_sha256);
        self::assertSame($beforeRestore->fecha_finalizacion->toISOString(), $restored->fecha_finalizacion->toISOString());
        self::assertSame('completada', $restored->estado_restauracion->value);
        self::assertSame($admin->id, $restored->restaurado_por_user_id);
        self::assertNotNull($restored->restaurado_at);
    }

    public function test_restore_journals_failure_when_database_cannot_reconnect(): void
    {
        config(['backup.restore_enabled' => true]);
        $admin = $this->user('restore-no-reconnect@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update(['checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo)]);
        $action = new class($this->storage, $this->restoreService, $this->lock, $this->journal, app(BitacoraService::class)) extends RestoreRespaldoAction
        {
            protected function reconnectAfterExternalProcess(): bool
            {
                return false;
            }
        };

        $this->expectException(BackupRestoreException::class);
        try {
            $action->execute($admin, $respaldo);
        } finally {
            self::assertSame(['restore_started', 'restore_failed'], array_column($this->journal->entries, 'evento'));
        }
    }

    public function test_restore_failure_is_controlled_and_journaled(): void
    {
        config(['backup.restore_enabled' => true]);
        $admin = $this->user('restore-failure@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update(['checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo)]);
        $this->restoreService->shouldFail = true;

        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'RESTAURAR'])
            ->assertUnprocessable();
        self::assertSame('fallida', $respaldo->fresh()->estado_restauracion->value);
        self::assertSame(['restore_started', 'restore_failed'], array_column($this->journal->entries, 'evento'));
        $this->assertDatabaseHas('bitacora', ['accion' => 'restauracion_fallida', 'entidad_id' => $respaldo->id]);
    }

    public function test_restore_requires_confirmation_state_and_lock(): void
    {
        config(['backup.restore_enabled' => true]);
        $admin = $this->user('restore-validation@example.test', 'Administrador');
        $respaldo = $this->completed();
        $this->storage->put($respaldo->ruta_archivo, 'SQL CONTENT');
        $respaldo->update(['checksum_sha256' => $this->storage->checksum($respaldo->ruta_archivo)]);

        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, [])->assertUnprocessable();
        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'NO'])->assertUnprocessable();
        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'RESTAURAR', 'path' => 'x'])->assertUnprocessable();
        $this->lock->occupied = true;
        $this->apiPost('/api/v1/respaldos/'.$respaldo->id.'/restaurar', $admin, ['confirmacion' => 'RESTAURAR'])->assertConflict();
    }

    public function test_real_process_services_refuse_testing_and_builder_does_not_expose_password(): void
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
            'backup.restore_enabled' => true,
        ]);
        $service = app(MariaDbBackupService::class, ['runner' => $runner]);
        $path = tempnam(sys_get_temp_dir(), 'backup-test-');
        $service->dumpTo($path);
        self::assertNotContains('secret-password', $runner->command);
        self::assertFalse((bool) array_filter($runner->command, fn (string $argument): bool => str_contains($argument, '--databases') || str_contains($argument, 'DROP DATABASE') || str_contains($argument, 'CREATE DATABASE')));
        $options = (string) collect($runner->command)->first(fn (string $argument): bool => str_starts_with($argument, '--defaults-extra-file='));
        self::assertNotSame('', $options);
        self::assertFileDoesNotExist(substr($options, strlen('--defaults-extra-file=')));
        @unlink($path);

        config(['app.env' => 'testing']);
        $this->expectException(BackupServiceException::class);
        app(MariaDbBackupService::class, ['runner' => $runner])->dumpTo('/tmp/never-executed.sql');
    }

    private function pending(): Respaldo
    {
        return Respaldo::create([
            'tipo' => 'manual',
            'estado' => 'pendiente',
            'nombre_archivo' => 'backup_20260928_020000_'.uniqid().'.sql',
            'ruta_archivo' => '2026/09/'.uniqid().'.sql',
        ]);
    }

    private function completed(): Respaldo
    {
        return Respaldo::create([
            'tipo' => 'manual',
            'estado' => 'completado',
            'nombre_archivo' => 'backup_20260928_020000_'.uniqid().'.sql',
            'ruta_archivo' => '2026/09/'.uniqid().'.sql',
            'checksum_sha256' => str_repeat('a', 64),
            'tamano_bytes' => 1,
        ]);
    }

    private function user(string $email, ?string $role = null): User
    {
        $user = User::create([
            'nombres' => 'Backup',
            'apellido_paterno' => 'Tester',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user->fresh();
    }

    private function apiGet(string $url, User $user): TestResponse
    {
        return $this->actingAs($user, 'web')->getJson($url);
    }

    private function apiPost(string $url, User $user, array $payload): TestResponse
    {
        return $this->actingAs($user, 'web')->postJson($url, $payload);
    }
}
