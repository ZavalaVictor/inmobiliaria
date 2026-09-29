<?php

namespace Tests\Feature\Api;

use App\Actions\Documentos\CreateDocumentoAction;
use App\Actions\Operaciones\AssignAgenteToOperacionAction;
use App\Actions\Operaciones\UpdateOperacionAction;
use App\Actions\Oportunidades\ChangeOportunidadEstadoAction;
use App\Actions\Oportunidades\ChangeOportunidadEtapaAction;
use App\Actions\Solicitudes\CreatePublicSolicitudInformacionAction;
use App\Actions\Solicitudes\CreateSolicitudInformacionAction;
use App\Contracts\BackupOperationLock;
use App\Contracts\BackupPrivateStorage;
use App\Contracts\DatabaseBackupService;
use App\Contracts\DocumentoPrivateStorage;
use App\Jobs\GenerateRespaldoJob;
use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\CategoriaDocumento;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\HistorialCorreo;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\Respaldo;
use App\Models\SolicitudInformacion;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\Respaldos\RespaldoNotificationDispatcher;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FakeBackupOperationLock;
use Tests\Fakes\FakeBackupPrivateStorage;
use Tests\Fakes\FakeDatabaseBackupService;
use Tests\Fakes\FakeDocumentoPrivateStorage;
use Tests\TestCase;

class DomainNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_private_and_public_requests_notify_the_approved_recipients_and_trace_relation(): void
    {
        $admin = $this->user('domain-request-admin@example.test', 'Administrador');
        $assistant = $this->user('domain-request-assistant@example.test', 'Asistente');

        $private = app(CreateSolicitudInformacionAction::class)->execute($admin, [
            'nombre' => 'Privada',
            'email' => 'private-request@example.test',
        ]);

        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $assistant->id)->count());
        self::assertSame(1, HistorialCorreo::query()->where('relacionado_type', SolicitudInformacion::class)->where('relacionado_id', $private->id)->count());
        self::assertSame('enviado', HistorialCorreo::query()->where('relacionado_id', $private->id)->firstOrFail()->estado->value);

        $public = app(CreatePublicSolicitudInformacionAction::class)->execute([
            'nombre' => 'Público',
            'email' => 'public-request@example.test',
        ]);

        self::assertSame(1, DB::table('notifications')->count());
        $publicHistory = HistorialCorreo::query()->where('relacionado_id', $public->id)->firstOrFail();
        self::assertNull($publicHistory->destinatario_user_id);
        self::assertSame('enviado', $publicHistory->estado->value);
    }

    public function test_assignment_email_uses_generic_relation_and_same_responsible_is_idempotent(): void
    {
        $admin = $this->user('domain-assignment-admin@example.test', 'Administrador');
        $responsible = $this->user('domain-assignment-responsible@example.test', 'Asistente');
        $request = SolicitudInformacion::create(['nombre' => 'Asignable', 'email' => 'request@example.test']);

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/solicitudes/'.$request->id, ['atendida_por_user_id' => $responsible->id])
            ->assertOk();
        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/solicitudes/'.$request->id, ['atendida_por_user_id' => $responsible->id])
            ->assertOk();

        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $responsible->id)->count());
        self::assertSame(1, HistorialCorreo::query()->where('relacionado_type', SolicitudInformacion::class)->where('relacionado_id', $request->id)->where('tipo', 'solicitud_asignada')->count());
    }

    public function test_opportunity_stage_and_terminal_events_use_agent_and_client_channels(): void
    {
        $admin = $this->user('domain-opportunity-admin@example.test', 'Administrador');
        $agentUser = $this->user('domain-opportunity-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'DOM-OPP-01']);
        $client = Cliente::create(['nombres' => 'Cliente', 'apellido_paterno' => 'Domain', 'email' => 'domain-client@example.test']);
        $owner = Propietario::create(['nombre_razon_social' => 'Owner OPP', 'rfc' => 'DOMOPP000001', 'telefono' => '5555555555', 'direccion' => 'Centro']);
        $category = Categoria::create(['nombre' => 'Domain OPP']);
        $property = Inmueble::create(['propietario_id' => $owner->id, 'categoria_id' => $category->id, 'codigo' => 'DOM-OPP', 'titulo' => 'Propiedad', 'slug' => 'dom-opp', 'tipo_operacion' => 'venta', 'precio_venta' => '100000', 'calle' => 'Calle', 'colonia' => 'Centro', 'municipio' => 'Municipio', 'estado_ubicacion' => 'Estado', 'codigo_postal' => '01000', 'publicado' => true]);
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'agente_principal_id' => $agent->id, 'titulo' => 'Oportunidad']);

        app(ChangeOportunidadEtapaAction::class)->execute($admin, $opportunity, ['etapa' => 'negociacion']);
        app(ChangeOportunidadEstadoAction::class)->execute($admin, $opportunity, ['estado' => 'ganada']);

        self::assertSame(2, DB::table('notifications')->where('notifiable_id', $agentUser->id)->count());
        self::assertSame(2, HistorialCorreo::query()->where('relacionado_type', Oportunidad::class)->where('relacionado_id', $opportunity->id)->where('tipo', 'oportunidad_cierre')->count());
        self::assertSame(1, HistorialCorreo::query()->where('relacionado_id', $opportunity->id)->where('destinatario_email', 'domain-client@example.test')->count());
    }

    public function test_operation_and_document_events_only_use_explicit_agents(): void
    {
        $admin = $this->user('domain-operation-admin@example.test', 'Administrador');
        $agentUser = $this->user('domain-operation-agent@example.test', 'Agente Inmobiliario');
        $agent = Agente::create(['user_id' => $agentUser->id, 'numero_empleado' => 'DOM-OP-01']);
        $client = Cliente::create(['nombres' => 'Operación cliente', 'apellido_paterno' => 'Domain', 'email' => 'operation-client@example.test']);
        $owner = Propietario::create(['nombre_razon_social' => 'Owner OP', 'rfc' => 'DOMOP0000001', 'telefono' => '5555555555', 'direccion' => 'Centro']);
        $category = Categoria::create(['nombre' => 'Domain OP']);
        $property = Inmueble::create(['propietario_id' => $owner->id, 'categoria_id' => $category->id, 'codigo' => 'DOM-OP', 'titulo' => 'Operación', 'slug' => 'dom-op', 'tipo_operacion' => 'venta', 'precio_venta' => '100000', 'calle' => 'Calle', 'colonia' => 'Centro', 'municipio' => 'Municipio', 'estado_ubicacion' => 'Estado', 'codigo_postal' => '01000', 'publicado' => true]);
        AgenteInmueble::create(['agente_id' => $agent->id, 'inmueble_id' => $property->id]);
        $opportunity = Oportunidad::create(['cliente_id' => $client->id, 'inmueble_id' => $property->id, 'titulo' => 'Operación oportunidad']);
        $operation = Operacion::create(['oportunidad_id' => $opportunity->id, 'cliente_id' => $client->id, 'inmueble_id' => $property->id, 'registrado_por_user_id' => $admin->id, 'tipo_operacion' => 'venta', 'monto' => '100000']);

        app(AssignAgenteToOperacionAction::class)->execute($operation, ['agente_id' => $agent->id], $admin);
        app(UpdateOperacionAction::class)->execute($admin, $operation, ['estado' => 'anulada']);

        $category = CategoriaDocumento::create(['nombre' => 'Domain docs']);
        $storage = new FakeDocumentoPrivateStorage;
        $this->app->instance(DocumentoPrivateStorage::class, $storage);
        app(CreateDocumentoAction::class)->execute($admin, UploadedFile::fake()->createWithContent('domain.pdf', '%PDF-1.4'), [
            'categoria_documento_id' => $category->id,
            'inmueble_id' => $property->id,
        ]);

        self::assertGreaterThanOrEqual(2, DB::table('notifications')->where('notifiable_id', $agentUser->id)->count());
        self::assertSame(1, HistorialCorreo::query()->where('relacionado_type', Documento::class)->count());
        self::assertSame(0, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
    }

    public function test_manual_backup_success_and_automatic_failure_use_approved_recipients(): void
    {
        $admin = $this->user('domain-backup-admin@example.test', 'Administrador');
        $storage = new FakeBackupPrivateStorage;
        $backup = new FakeDatabaseBackupService;
        $lock = new FakeBackupOperationLock;
        $this->app->instance(BackupPrivateStorage::class, $storage);
        $this->app->instance(DatabaseBackupService::class, $backup);
        $this->app->instance(BackupOperationLock::class, $lock);

        $manual = Respaldo::create([
            'generado_por_user_id' => $admin->id,
            'tipo' => 'manual',
            'estado' => 'pendiente',
            'nombre_archivo' => 'backup_domain.sql',
            'ruta_archivo' => '2026/09/backup_domain.sql',
        ]);
        (new GenerateRespaldoJob($manual->id))->handle($backup, $storage, $lock, app(BitacoraService::class), app(RespaldoNotificationDispatcher::class));

        self::assertSame(1, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
        self::assertSame(1, HistorialCorreo::query()->where('relacionado_type', Respaldo::class)->where('relacionado_id', $manual->id)->count());

        $automatic = Respaldo::create([
            'generado_por_user_id' => null,
            'tipo' => 'automatico',
            'estado' => 'pendiente',
            'nombre_archivo' => 'backup_auto.sql',
            'ruta_archivo' => '2026/09/backup_auto.sql',
        ]);
        $backup->shouldFail = true;
        (new GenerateRespaldoJob($automatic->id))->handle($backup, $storage, $lock, app(BitacoraService::class), app(RespaldoNotificationDispatcher::class));

        self::assertSame(2, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
        self::assertSame('fallido', $automatic->fresh()->estado->value);
        self::assertSame('enviado', HistorialCorreo::query()->where('relacionado_id', $automatic->id)->firstOrFail()->estado->value);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create(['nombres' => 'Domain', 'apellido_paterno' => 'User', 'email' => $email, 'password' => 'Password123', 'estado' => 'activo']);
        $user->assignRole($role);

        return $user->fresh();
    }
}
