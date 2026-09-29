<?php

namespace Tests\Feature\Api;

use App\Jobs\GenerateRespaldoJob;
use App\Models\ConfiguracionRespaldo;
use App\Models\Respaldo;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConfiguracionRespaldosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Queue::fake();
    }

    public function test_empty_configuration_returns_safe_defaults_without_creating_row(): void
    {
        $admin = $this->user('config-defaults@example.test', 'Administrador');
        $this->actingAs($admin, 'web')->getJson('/api/v1/configuracion-respaldos')
            ->assertOk()
            ->assertJsonPath('data.id', null)
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.frecuencia', 'diario')
            ->assertJsonPath('data.hora_ejecucion', '02:00:00')
            ->assertJsonPath('data.retencion_dias', 30);
        self::assertDatabaseCount('configuracion_respaldos', 0);
    }

    public function test_patch_creates_singleton_and_validates_schedule_combinations(): void
    {
        $admin = $this->user('config-patch@example.test', 'Administrador');
        $url = '/api/v1/configuracion-respaldos';

        $this->actingAs($admin, 'web')->patchJson($url, [
            'activo' => true,
            'frecuencia' => 'semanal',
            'hora_ejecucion' => '03:15:00',
            'dia_semana' => 2,
            'retencion_dias' => 45,
        ])->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.frecuencia', 'semanal')
            ->assertJsonPath('data.dia_semana', 2)
            ->assertJsonPath('data.retencion_dias', 45);

        $configuration = ConfiguracionRespaldo::query()->firstOrFail();
        self::assertSame($admin->id, $configuration->actualizado_por_user_id);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'configuracion_respaldo_actualizada',
            'entidad_id' => $configuration->id,
        ]);

        $this->actingAs($admin, 'web')->patchJson($url, [
            'frecuencia' => 'diario',
            'dia_semana' => 2,
        ])->assertUnprocessable();
        $this->actingAs($admin, 'web')->patchJson($url, [
            'frecuencia' => 'mensual',
            'dia_mes' => 29,
        ])->assertUnprocessable();
    }

    public function test_multiple_rows_fail_closed_and_internal_fields_are_prohibited(): void
    {
        $admin = $this->user('config-multiple@example.test', 'Administrador');
        ConfiguracionRespaldo::create();
        ConfiguracionRespaldo::create();

        $this->actingAs($admin, 'web')->getJson('/api/v1/configuracion-respaldos')->assertConflict();
        $this->actingAs($admin, 'web')->patchJson('/api/v1/configuracion-respaldos', [
            'ruta_destino' => '/etc',
        ])->assertUnprocessable();
    }

    public function test_config_permissions_fail_closed(): void
    {
        $admin = $this->user('config-permission@example.test', 'Administrador');
        Role::findByName('Administrador', 'web')->revokePermissionTo('configuracion_respaldos.actualizar');
        $this->actingAs($admin, 'web')->patchJson('/api/v1/configuracion-respaldos', [
            'activo' => true,
        ])->assertForbidden();
    }

    public function test_retention_is_persisted_without_cleaning_files(): void
    {
        $admin = $this->user('config-retention@example.test', 'Administrador');
        $this->actingAs($admin, 'web')->patchJson('/api/v1/configuracion-respaldos', [
            'retencion_dias' => 1,
        ])->assertOk();
        self::assertSame(1, ConfiguracionRespaldo::query()->value('retencion_dias'));
        self::assertDatabaseCount('respaldos', 0);
    }

    public function test_due_command_claims_once_and_dispatches_the_common_job(): void
    {
        $this->user('config-scheduler@example.test', 'Administrador');
        ConfiguracionRespaldo::create([
            'activo' => true,
            'frecuencia' => 'diario',
            'hora_ejecucion' => '00:00:00',
            'dia_semana' => null,
            'dia_mes' => null,
        ]);

        $this->artisan('backups:run-due')->assertExitCode(0);

        $configuration = ConfiguracionRespaldo::query()->firstOrFail();
        self::assertNotNull($configuration->ultima_ejecucion_at);
        $this->assertDatabaseHas('respaldos', [
            'tipo' => 'automatico',
            'generado_por_user_id' => null,
            'estado' => 'pendiente',
        ]);
        Queue::assertPushed(GenerateRespaldoJob::class);

        $count = Respaldo::query()->count();
        $this->artisan('backups:run-due')->assertExitCode(0);
        self::assertSame($count, Respaldo::query()->count());
    }

    public function test_scheduler_fails_closed_for_multiple_configurations(): void
    {
        ConfiguracionRespaldo::create();
        ConfiguracionRespaldo::create();

        $this->artisan('backups:run-due')->assertExitCode(0);

        self::assertDatabaseCount('respaldos', 0);
    }

    public function test_scheduler_evaluates_weekly_and_monthly_windows(): void
    {
        $this->travelTo(Carbon::create(2026, 1, 15, 12, 0, 0));

        ConfiguracionRespaldo::create([
            'activo' => true,
            'frecuencia' => 'semanal',
            'hora_ejecucion' => '00:00:00',
            'dia_semana' => now()->isoWeekday(),
        ]);
        $this->artisan('backups:run-due')->assertExitCode(0);
        self::assertDatabaseCount('respaldos', 1);

        Respaldo::query()->delete();
        ConfiguracionRespaldo::query()->delete();
        ConfiguracionRespaldo::create([
            'activo' => true,
            'frecuencia' => 'mensual',
            'hora_ejecucion' => '00:00:00',
            'dia_mes' => now()->day,
        ]);
        $this->artisan('backups:run-due')->assertExitCode(0);
        self::assertDatabaseCount('respaldos', 1);

        $this->travelBack();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Config',
            'apellido_paterno' => 'Tester',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }
}
