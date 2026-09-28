<?php

namespace Tests\Feature\Api;

use App\Actions\Agentes\CreateAgenteAction;
use App\Actions\Agentes\DeleteAgenteAction;
use App\Actions\Agentes\UpdateAgenteAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\SyncUserRolesAction;
use App\Actions\Users\UpdateUserAction;
use App\Models\Bitacora;
use App\Models\User;
use App\Services\BitacoraService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BitacoraAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_and_agent_actions_record_controlled_events_and_snapshots(): void
    {
        $admin = $this->user('audit-admin@example.test', 'Administrador');
        $created = app(CreateUserAction::class)->execute([
            'nombres' => 'Auditado',
            'apellido_paterno' => 'Inicial',
            'email' => 'audited@example.test',
            'password' => 'Password123',
            'estado' => 'activo',
        ], $admin);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'usuario_creado',
            'entidad' => 'usuario',
            'entidad_id' => $created->id,
            'user_id' => $admin->id,
        ]);
        self::assertSame(1, Bitacora::query()
            ->where('accion', 'usuario_creado')
            ->where('entidad_id', $created->id)
            ->count());

        app(UpdateUserAction::class)->execute($created, ['telefono' => '5550000000', 'estado' => 'bloqueado'], $admin);
        self::assertSame('bloqueado', Bitacora::query()->where('accion', 'usuario_actualizado')->latest('id')->value('datos_nuevos')['estado']);

        app(SyncUserRolesAction::class)->execute($created, ['Asistente'], $admin);
        $rolesEvent = Bitacora::query()->where('accion', 'usuario_roles_actualizados')->latest('id')->firstOrFail();
        self::assertSame(['roles' => ['Asistente']], $rolesEvent->datos_nuevos);

        app(DeleteUserAction::class)->execute($created, $admin);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'usuario_eliminado',
            'entidad' => 'usuario',
            'entidad_id' => $created->id,
            'user_id' => $admin->id,
        ]);

        $agentUser = $this->user('audit-agent@example.test', 'Agente Inmobiliario');
        $agent = app(CreateAgenteAction::class)->execute([
            'user_id' => $agentUser->id,
            'numero_empleado' => 'AUD-001',
            'porcentaje_comision' => '3.50',
            'estado_laboral' => 'activo',
        ], $admin);
        app(UpdateAgenteAction::class)->execute($agent, ['porcentaje_comision' => '4.25'], $admin);

        $agentEvent = Bitacora::query()->where('accion', 'agente_actualizado')->latest('id')->firstOrFail();
        self::assertSame('4.25', (string) $agentEvent->datos_nuevos['porcentaje_comision']);
        self::assertArrayNotHasKey('password', $agentEvent->datos_nuevos);
        self::assertSame($admin->id, $agentEvent->user_id);

        app(DeleteAgenteAction::class)->execute($agent, $admin);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'agente_eliminado',
            'entidad' => 'agente',
            'entidad_id' => $agent->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_bitacora_failure_rolls_back_a_critical_user_mutation(): void
    {
        $admin = $this->user('audit-rollback-admin@example.test', 'Administrador');
        $beforeBitacoraCount = Bitacora::query()->count();
        $this->app->instance(BitacoraService::class, \Mockery::mock(BitacoraService::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        }));

        try {
            app(CreateUserAction::class)->execute([
                'nombres' => 'No persistir',
                'apellido_paterno' => 'Auditoría',
                'email' => 'rollback-audit@example.test',
                'password' => 'Password123',
                'estado' => 'activo',
            ], $admin);
            self::fail('La mutación debía revertirse cuando Bitácora falla.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit unavailable', $exception->getMessage());
        }

        self::assertDatabaseMissing('users', ['email' => 'rollback-audit@example.test']);
        self::assertSame($beforeBitacoraCount, Bitacora::query()->count());
    }

    public function test_service_rejects_uncontrolled_catalog_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(BitacoraService::class)->record(null, 'arbitrary', 'usuario');
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Auditor',
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }
}
