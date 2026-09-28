<?php

namespace Tests\Feature\Api;

use App\Actions\Documentos\CreateDocumentoAction;
use App\Contracts\DocumentoPrivateStorage;
use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\CategoriaDocumento;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Models\Documento;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Models\Oportunidad;
use App\Models\Propietario;
use App\Models\User;
use App\Services\BitacoraService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Fakes\FakeDocumentoPrivateStorage;
use Tests\TestCase;
use Throwable;

class DocumentosTest extends TestCase
{
    use RefreshDatabase;

    private FakeDocumentoPrivateStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->storage = new FakeDocumentoPrivateStorage;
        $this->app->instance(DocumentoPrivateStorage::class, $this->storage);
    }

    public function test_documents_require_authentication_and_follow_the_role_matrix(): void
    {
        $this->getJson('/api/v1/documentos')->assertUnauthorized();

        $admin = $this->user('document-admin@example.test', 'Administrador');
        $agent = $this->agentUser('document-agent@example.test');
        $property = $this->property('matrix');
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        $document = $this->createDocument($admin, ['inmueble_id' => $property->id]);

        $this->apiGet('/api/v1/documentos', $admin)->assertOk();
        $this->apiGet('/api/v1/documentos/'.$document->id, $agent)->assertOk();
        $this->apiPost('/api/v1/documentos', $agent, [
            'archivo' => $this->pdf(),
            'categoria_documento_id' => $document->categoria_documento_id,
            'inmueble_id' => $property->id,
        ])->assertCreated();

        foreach (['Asistente', 'Director General', 'Cliente'] as $role) {
            $user = $this->user(strtolower(str_replace(' ', '-', $role)).'@document-matrix.example.test', $role);
            $this->apiGet('/api/v1/documentos', $user)->assertForbidden();
            $this->apiGet('/api/v1/documentos/'.$document->id, $user)->assertForbidden();
        }

        $orphanAgent = $this->agentUser('document-orphan@example.test');
        $this->apiGet('/api/v1/documentos', $orphanAgent)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_can_upload_each_real_destination_with_derived_metadata(): void
    {
        $admin = $this->user('document-destinations@example.test', 'Administrador');
        $category = $this->category('Contratos');
        $owner = $this->owner('OWNERDEST001');
        $client = $this->client('Cliente destino');
        $property = $this->property('destinations', $owner);
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'titulo' => 'Oportunidad documentos',
        ]);
        $operation = Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $admin->id,
            'tipo_operacion' => 'venta',
            'monto' => '100000.00',
        ]);

        foreach ([
            ['propietario_id' => $owner->id, 'type' => 'propietarios'],
            ['cliente_id' => $client->id, 'type' => 'clientes'],
            ['inmueble_id' => $property->id, 'type' => 'inmuebles'],
            ['operacion_id' => $operation->id, 'type' => 'operaciones'],
        ] as $index => $destination) {
            $destinationId = $destination[array_key_first(array_diff_key($destination, ['type' => true]))];
            $destinationType = $destination['type'];
            unset($destination['type']);
            $response = $this->upload($admin, $category, $destination, 'contrato-'.$index.'.jpg', 'application/pdf')
                ->assertCreated()
                ->assertJsonMissingPath('data.firebase_path')
                ->assertJsonMissingPath('data.bucket');

            $document = Documento::query()->findOrFail($response->json('data.id'));
            self::assertSame('application/pdf', $document->mime_type->value);
            self::assertSame($admin->id, $document->subido_por_user_id);
            self::assertMatchesRegularExpression(
                '~^documentos/'.$destinationType.'/'.$destinationId.'/[0-9a-f-]{36}\.pdf$~i',
                $document->firebase_path
            );
            self::assertSame('contrato-'.$index.'.jpg', $document->nombre_original);
        }
    }

    public function test_agent_can_create_only_for_each_destination_within_scope(): void
    {
        $agent = $this->agentUser('document-scoped-agent@example.test');
        $category = $this->category('Identificaciones');
        $client = $this->client('Cliente asignado');
        $owner = $this->owner('OWNERSCOPE01');
        $property = $this->property('scoped', $owner);
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'titulo' => 'Oportunidad con documento',
        ]);
        $operation = Operacion::create([
            'oportunidad_id' => $opportunity->id,
            'cliente_id' => $client->id,
            'inmueble_id' => $property->id,
            'registrado_por_user_id' => $agent->id,
            'tipo_operacion' => 'venta',
            'monto' => '90000.00',
        ]);
        ClienteAgente::create(['cliente_id' => $client->id, 'agente_id' => $agent->agente->id]);
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $property->id]);
        OperacionAgente::create(['operacion_id' => $operation->id, 'agente_id' => $agent->agente->id]);

        foreach ([
            ['cliente_id' => $client->id],
            ['inmueble_id' => $property->id],
            ['propietario_id' => $owner->id],
            ['operacion_id' => $operation->id],
        ] as $destination) {
            $this->upload($agent, $category, $destination)->assertCreated();
        }

        $otherClient = $this->client('Cliente fuera de alcance');
        $otherProperty = $this->property('outside');
        $this->upload($agent, $category, ['cliente_id' => $otherClient->id])->assertForbidden();
        $this->upload($agent, $category, ['inmueble_id' => $otherProperty->id])->assertForbidden();
    }

    public function test_destination_category_file_and_internal_field_validation(): void
    {
        $admin = $this->user('document-validation@example.test', 'Administrador');
        $category = $this->category('Validación');
        $property = $this->property('validation');

        $this->upload($admin, $category, [])->assertUnprocessable();
        $this->upload($admin, $category, ['cliente_id' => 1, 'inmueble_id' => $property->id])->assertUnprocessable();
        $this->upload($admin, $category, ['inmueble_id' => 999999])->assertUnprocessable();
        $this->upload($admin, $category, ['inmueble_id' => $property->id], 'archivo.txt', 'text/plain')->assertUnprocessable();
        $this->upload($admin, $category, ['inmueble_id' => $property->id], 'falsa.jpg', 'application/pdf')
            ->assertCreated();
        self::assertStringEndsWith('.pdf', (string) Documento::query()->latest('id')->value('firebase_path'));

        $inactive = $this->category('Inactiva', false);
        $this->upload($admin, $inactive, ['inmueble_id' => $property->id])->assertUnprocessable();

        $this->upload($admin, $category, ['inmueble_id' => $property->id], 'prohibited.pdf', 'application/pdf', [
            'firebase_path' => 'attacker/path.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => 1,
            'subido_por_user_id' => 999,
        ])->assertUnprocessable();
    }

    public function test_supported_png_jpeg_and_configured_size_limit_are_enforced(): void
    {
        $admin = $this->user('document-mime-size@example.test', 'Administrador');
        $category = $this->category('MIME y tamaño');
        $property = $this->property('mime-size');

        $this->uploadFile($admin, $category, ['inmueble_id' => $property->id], UploadedFile::fake()->image('imagen.png'))
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/png');
        $this->uploadFile($admin, $category, ['inmueble_id' => $property->id], UploadedFile::fake()->image('imagen.jpg'))
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/jpeg');
        $this->upload($admin, $category, ['inmueble_id' => $property->id], 'grande.pdf', 'application/pdf', [], 10241)
            ->assertUnprocessable();
    }

    public function test_upload_compensation_download_update_and_delete_are_private(): void
    {
        $admin = $this->user('document-storage@example.test', 'Administrador');
        $category = $this->category('Storage');
        $property = $this->property('storage');
        $response = $this->upload($admin, $category, ['inmueble_id' => $property->id], 'privado.pdf')
            ->assertCreated();
        $document = Documento::query()->findOrFail($response->json('data.id'));
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'documento_creado',
            'entidad' => 'documento',
            'entidad_id' => $document->id,
            'user_id' => $admin->id,
        ]);

        $this->apiGet('/api/v1/documentos/'.$document->id.'/descargar', $admin)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=privado.pdf');

        $newCategory = $this->category('Nueva categoría');
        $this->apiPatch('/api/v1/documentos/'.$document->id, $admin, [
            'categoria_documento_id' => $newCategory->id,
            'fecha_documento' => '2026-01-01',
            'fecha_vencimiento' => '2026-12-31',
            'observaciones' => 'Actualizado',
        ])->assertOk()->assertJsonPath('data.observaciones', 'Actualizado');
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'documento_actualizado',
            'entidad' => 'documento',
            'entidad_id' => $document->id,
            'user_id' => $admin->id,
        ]);

        $this->apiPatch('/api/v1/documentos/'.$document->id, $admin, [
            'fecha_vencimiento' => '2025-01-01',
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/documentos/'.$document->id, $admin, [
            'inmueble_id' => $property->id,
            'archivo' => 'forbidden',
            'firebase_path' => 'forbidden',
            'nombre_original' => 'changed.pdf',
        ])->assertUnprocessable();

        $path = $document->firebase_path;
        $this->apiDelete('/api/v1/documentos/'.$document->id, $admin)->assertNoContent();
        self::assertSoftDeleted('documentos', ['id' => $document->id]);
        self::assertContains($path, $this->storage->deletes);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'documento_eliminado',
            'entidad' => 'documento',
            'entidad_id' => $document->id,
            'user_id' => $admin->id,
        ]);
        $this->apiGet('/api/v1/documentos/'.$document->id, $admin)->assertNotFound();

        $second = $this->createDocument($admin, ['inmueble_id' => $property->id]);
        $this->storage->failDelete = true;
        $this->apiDelete('/api/v1/documentos/'.$second->id, $admin)->assertNoContent();
        self::assertSoftDeleted('documentos', ['id' => $second->id]);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'documento_eliminado',
            'entidad' => 'documento',
            'entidad_id' => $second->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_bitacora_failure_compensates_document_upload_and_rolls_back_metadata(): void
    {
        $admin = $this->user('document-bitacora-failure@example.test', 'Administrador');
        $category = $this->category('Bitácora falla');
        $property = $this->property('bitacora-failure');
        $this->app->instance(BitacoraService::class, \Mockery::mock(BitacoraService::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        }));

        try {
            app(CreateDocumentoAction::class)->execute($admin, $this->pdf(), [
                'categoria_documento_id' => $category->id,
                'inmueble_id' => $property->id,
            ]);
            self::fail('La creación debía fallar cuando Bitácora no está disponible.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit unavailable', $exception->getMessage());
        }

        self::assertCount(0, $this->storage->objects);
        self::assertDatabaseCount('documentos', 0);
        self::assertDatabaseCount('bitacora', 0);
    }

    public function test_failed_database_insert_compensates_uploaded_private_object(): void
    {
        $admin = $this->user('document-compensation@example.test', 'Administrador');
        $category = $this->category('Compensación');
        $property = $this->property('compensation');

        try {
            app(CreateDocumentoAction::class)->execute($admin, $this->pdf(), [
                'categoria_documento_id' => $category->id,
                'inmueble_id' => $property->id,
                'observaciones' => str_repeat('x', 501),
            ]);
            self::fail('La inserción debía fallar por la longitud de observaciones.');
        } catch (Throwable) {
            // The action must compensate the already uploaded private object.
        }

        self::assertCount(0, $this->storage->objects);
        self::assertDatabaseCount('documentos', 0);
    }

    public function test_index_filters_and_search_never_expand_agent_scope(): void
    {
        $admin = $this->user('document-index-admin@example.test', 'Administrador');
        $agent = $this->agentUser('document-index-agent@example.test');
        $assigned = $this->property('index-assigned');
        $other = $this->property('index-other');
        AgenteInmueble::create(['agente_id' => $agent->agente->id, 'inmueble_id' => $assigned->id]);
        $category = $this->category('Index');
        $visible = $this->createDocument($admin, ['inmueble_id' => $assigned->id, 'observaciones' => 'Visible para agente'], $category);
        $hidden = $this->createDocument($admin, ['inmueble_id' => $other->id, 'observaciones' => 'Secreto fuera de alcance'], $category);

        $this->apiGet('/api/v1/documentos?q=Secreto', $agent)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->apiGet('/api/v1/documentos?destino=inmueble&categoria_documento_id='.$category->id, $admin)
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->apiGet('/api/v1/documentos?sort=nombre_original&direction=asc&per_page=1', $admin)
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        self::assertNotSame($visible->id, $hidden->id);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Documento',
            'email' => $email,
            'password' => 'Password123',
            'estado' => 'activo',
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function agentUser(string $email): User
    {
        $user = $this->user($email, 'Agente Inmobiliario');
        Agente::create([
            'user_id' => $user->id,
            'numero_empleado' => strtoupper(substr(md5($email), 0, 10)),
        ]);

        return $user->fresh(['agente']);
    }

    private function category(string $name, bool $active = true): CategoriaDocumento
    {
        return CategoriaDocumento::create(['nombre' => $name, 'activo' => $active]);
    }

    private function owner(string $rfc): Propietario
    {
        return Propietario::create([
            'nombre_razon_social' => 'Propietario '.$rfc,
            'rfc' => $rfc,
            'telefono' => '5555555555',
            'direccion' => 'Dirección',
        ]);
    }

    private function client(string $name): Cliente
    {
        return Cliente::create([
            'nombres' => $name,
            'apellido_paterno' => 'Documento',
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'estado_cliente' => 'prospecto',
        ]);
    }

    private function property(string $suffix, ?Propietario $owner = null): Inmueble
    {
        $owner ??= $this->owner('RFC'.strtoupper(substr(md5($suffix), 0, 10)));
        $category = Categoria::create(['nombre' => 'Inmueble '.$suffix]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'DOC-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-documento-'.$suffix,
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Documento 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '01000',
        ]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf');
    }

    /** @param array<string, mixed> $destination */
    private function createDocument(User $user, array $destination, ?CategoriaDocumento $category = null): Documento
    {
        $category ??= $this->category('Auto '.uniqid());

        return Documento::create(array_merge([
            'categoria_documento_id' => $category->id,
            'subido_por_user_id' => $user->id,
            'nombre_original' => 'documento.pdf',
            'firebase_path' => 'documentos/test/'.uniqid().'.pdf',
            'mime_type' => 'application/pdf',
            'observaciones' => 'Documento de prueba',
        ], $destination));
    }

    /**
     * @param  array<string, mixed>  $destination
     * @param  array<string, mixed>  $extra
     */
    private function upload(
        User $user,
        CategoriaDocumento $category,
        array $destination,
        string $filename = 'documento.pdf',
        string $mime = 'application/pdf',
        array $extra = [],
        int $sizeKb = 10,
    ): TestResponse {
        return $this->uploadFile(
            $user,
            $category,
            $destination,
            UploadedFile::fake()->create($filename, $sizeKb, $mime),
            $extra,
        );
    }

    /** @param array<string, mixed> $destination */
    private function uploadFile(
        User $user,
        CategoriaDocumento $category,
        array $destination,
        UploadedFile $file,
        array $extra = [],
    ): TestResponse {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/documentos', array_merge([
                'archivo' => $file,
                'categoria_documento_id' => $category->id,
            ], $destination, $extra));
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPost(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson($uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson($uri, $payload);
    }

    private function apiDelete(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson($uri);
    }
}
