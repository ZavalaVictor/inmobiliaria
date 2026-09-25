<?php

namespace Tests\Feature\Api;

use App\Actions\InmuebleImagenes\UploadInmuebleImagenAction;
use App\Contracts\InmuebleImageStorage;
use App\Models\Agente;
use App\Models\AgenteInmueble;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use App\Models\Propietario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Fakes\FakeInmuebleImageStorage;
use Tests\TestCase;
use Throwable;

class InmuebleImagenesTest extends TestCase
{
    use RefreshDatabase;

    private FakeInmuebleImageStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->storage = new FakeInmuebleImageStorage;
        $this->app->instance(InmuebleImageStorage::class, $this->storage);
    }

    public function test_unauthenticated_user_cannot_list_images(): void
    {
        $inmueble = $this->property('unauthenticated');

        $this->getJson('/api/v1/inmuebles/'.$inmueble->id.'/imagenes')
            ->assertUnauthorized();
    }

    public function test_administrator_can_upload_list_and_receive_safe_resource(): void
    {
        $administrator = $this->user('admin-images@example.test', 'Administrador');
        $inmueble = $this->property('admin-images');

        $response = $this->upload($administrator, $inmueble, 'fachada.jpg', 'Fachada principal')
            ->assertCreated()
            ->assertJsonPath('data.inmueble_id', $inmueble->id)
            ->assertJsonPath('data.nombre_original', 'fachada.jpg')
            ->assertJsonPath('data.mime_type', 'image/jpeg')
            ->assertJsonPath('data.es_principal', true)
            ->assertJsonPath('data.orden', 0)
            ->assertJsonPath('data.texto_alternativo', 'Fachada principal')
            ->assertJsonMissingPath('data.firebase_path');

        self::assertCount(1, $this->storage->uploads);
        self::assertMatchesRegularExpression(
            '~^inmuebles/'.$inmueble->id.'/[0-9a-f-]{36}\.jpg$~i',
            $this->storage->uploads[0]['path']
        );

        $this->apiGet('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $response->json('data.id'));
    }

    public function test_upload_supports_jpeg_and_png_and_assigns_incremental_order(): void
    {
        $administrator = $this->user('admin-image-types@example.test', 'Administrador');
        $inmueble = $this->property('image-types');

        $this->upload($administrator, $inmueble, 'one.jpg')->assertCreated();
        $this->upload($administrator, $inmueble, 'two.png')->assertCreated();

        $images = $inmueble->imagenes()->orderBy('orden')->get();

        self::assertSame(['image/jpeg', 'image/png'], $images->pluck('mime_type')->all());
        self::assertSame([0, 1], $images->pluck('orden')->all());
        self::assertSame([true, false], $images->pluck('es_principal')->all());
    }

    public function test_upload_supports_real_webp_content_when_gd_is_available(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('PHP GD with WebP support is unavailable.');
        }

        $administrator = $this->user('admin-image-webp@example.test', 'Administrador');
        $inmueble = $this->property('image-webp');
        $path = tempnam(sys_get_temp_dir(), 'inmueble-webp-');
        $image = imagecreatetruecolor(2, 2);

        try {
            imagewebp($image, $path);
            $file = new UploadedFile($path, 'valid.webp', 'image/webp', UPLOAD_ERR_OK, true);

            $this->withHeaders(['Accept' => 'application/json'])
                ->actingAs($administrator, 'web')
                ->post('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', [
                    'imagen' => $file,
                ])
                ->assertCreated()
                ->assertJsonPath('data.mime_type', 'image/webp');
        } finally {
            imagedestroy($image);
            @unlink($path);
        }
    }

    public function test_invalid_mime_and_oversized_file_are_rejected(): void
    {
        $administrator = $this->user('admin-image-validation@example.test', 'Administrador');
        $inmueble = $this->property('image-validation');

        $this->uploadFile($administrator, $inmueble, UploadedFile::fake()->create(
            'document.pdf',
            100,
            'application/pdf'
        ))->assertUnprocessable();

        $this->uploadFile($administrator, $inmueble, UploadedFile::fake()->create(
            'too-large.jpg',
            10241,
            'image/jpeg'
        ))->assertUnprocessable();

        self::assertCount(0, $this->storage->uploads);
        self::assertDatabaseCount('inmueble_imagenes', 0);
    }

    public function test_upload_rejects_internal_payload_fields(): void
    {
        $administrator = $this->user('admin-image-prohibited@example.test', 'Administrador');
        $inmueble = $this->property('image-prohibited');

        foreach ([
            'inmueble_id',
            'firebase_path',
            'url_publica',
            'nombre_original',
            'mime_type',
            'tamano_bytes',
            'es_principal',
            'orden',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $field) {
            $this->uploadFile($administrator, $inmueble, UploadedFile::fake()->image('image.jpg'), [
                $field => 'forbidden',
            ])->assertUnprocessable();
        }
    }

    public function test_metadata_update_rejects_internal_fields(): void
    {
        $administrator = $this->user('admin-image-metadata-prohibited@example.test', 'Administrador');
        $inmueble = $this->property('metadata-prohibited');
        $imagen = $this->uploadRecord($administrator, $inmueble);

        foreach ([
            'inmueble_id',
            'firebase_path',
            'url_publica',
            'nombre_original',
            'mime_type',
            'tamano_bytes',
            'es_principal',
            'orden',
            'created_at',
            'updated_at',
            'deleted_at',
        ] as $field) {
            $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$imagen->id, $administrator, [
                $field => 'forbidden',
            ])->assertUnprocessable();
        }
    }

    public function test_agent_can_only_manage_images_for_assigned_property(): void
    {
        $agent = $this->agentUser('agent-images@example.test');
        $assigned = $this->property('agent-assigned');
        $unassigned = $this->property('agent-unassigned');
        $this->assignProperty($assigned, $agent->agente);
        $otherImage = $this->uploadRecord($this->user('admin-agent-image@example.test', 'Administrador'), $unassigned);

        $this->apiGet('/api/v1/inmuebles/'.$assigned->id.'/imagenes', $agent)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$unassigned->id.'/imagenes', $agent)->assertForbidden();
        $this->upload($agent, $assigned, 'agent.jpg')->assertCreated();
        $uploadsBeforeForbiddenRequest = count($this->storage->uploads);
        $this->upload($agent, $unassigned, 'forbidden.jpg')->assertForbidden();
        self::assertCount($uploadsBeforeForbiddenRequest, $this->storage->uploads);
        $this->apiPatch('/api/v1/inmuebles/'.$unassigned->id.'/imagenes/'.$otherImage->id, $agent, [
            'texto_alternativo' => 'No permitido',
        ])->assertForbidden();
    }

    public function test_assistant_can_create_and_update_but_not_delete_images(): void
    {
        $assistant = $this->user('assistant-images@example.test', 'Asistente');
        $inmueble = $this->property('assistant-images');
        $imagen = $this->uploadRecord($assistant, $inmueble);

        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$imagen->id, $assistant, [
            'texto_alternativo' => 'Texto actualizado',
        ])->assertOk();
        $this->apiDelete('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$imagen->id, $assistant)
            ->assertForbidden();
    }

    public function test_director_and_client_can_read_only_with_their_scope(): void
    {
        $director = $this->user('director-images@example.test', 'Director General');
        $clientUser = $this->user('client-images@example.test', 'Cliente');
        $client = Cliente::create([
            'user_id' => $clientUser->id,
            'nombres' => 'Cliente Imagen',
            'apellido_paterno' => 'Prueba',
            'email' => $clientUser->email,
        ]);
        $inmueble = $this->property('client-images');
        $foreign = $this->property('client-images-foreign');
        $this->interest($client, $inmueble);
        $image = $this->uploadRecord($this->user('admin-client-image@example.test', 'Administrador'), $inmueble);
        $this->uploadRecord($this->user('admin-foreign-image@example.test', 'Administrador'), $foreign);

        $this->apiGet('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', $director)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', $clientUser)->assertOk();
        $this->apiGet('/api/v1/inmuebles/'.$foreign->id.'/imagenes', $clientUser)->assertForbidden();
        $this->upload($director, $inmueble, 'director-forbidden.jpg')->assertForbidden();
        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$image->id, $director, [
            'texto_alternativo' => 'No permitido',
        ])->assertForbidden();
        $this->apiDelete('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$image->id, $clientUser)
            ->assertForbidden();
    }

    public function test_scoped_binding_rejects_image_from_another_property(): void
    {
        $administrator = $this->user('admin-image-binding@example.test', 'Administrador');
        $first = $this->property('binding-first');
        $second = $this->property('binding-second');
        $image = $this->uploadRecord($administrator, $second);

        $this->apiPatch('/api/v1/inmuebles/'.$first->id.'/imagenes/'.$image->id, $administrator, [
            'texto_alternativo' => 'No permitido',
        ])->assertNotFound();
    }

    public function test_principal_can_change_and_delete_promotes_next_image(): void
    {
        $administrator = $this->user('admin-image-principal@example.test', 'Administrador');
        $inmueble = $this->property('principal');
        $first = $this->uploadRecord($administrator, $inmueble, 'first.jpg');
        $second = $this->uploadRecord($administrator, $inmueble, 'second.jpg');

        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$second->id.'/principal', $administrator)
            ->assertOk();
        self::assertTrue((bool) $second->fresh()->es_principal);
        self::assertFalse((bool) $first->fresh()->es_principal);

        $this->apiDelete('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$second->id, $administrator)
            ->assertNoContent();
        self::assertSoftDeleted('inmueble_imagenes', ['id' => $second->id]);
        self::assertTrue((bool) $first->fresh()->es_principal);
        self::assertContains($second->firebase_path, $this->storage->deletes);

        $this->apiDelete('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$first->id, $administrator)
            ->assertNoContent();
        self::assertSame(0, $inmueble->imagenes()->count());
    }

    public function test_gallery_excludes_soft_deleted_images(): void
    {
        $administrator = $this->user('admin-image-gallery-soft-delete@example.test', 'Administrador');
        $inmueble = $this->property('gallery-soft-delete');
        $active = $this->uploadRecord($administrator, $inmueble);
        $deleted = $this->uploadRecord($administrator, $inmueble, 'deleted.jpg');
        $deleted->delete();

        $this->apiGet('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', $administrator)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_remote_delete_failure_does_not_restore_soft_deleted_image(): void
    {
        $administrator = $this->user('admin-image-delete-failure@example.test', 'Administrador');
        $inmueble = $this->property('delete-failure');
        $image = $this->uploadRecord($administrator, $inmueble);
        $this->storage->failDelete = true;

        $this->apiDelete('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/'.$image->id, $administrator)
            ->assertNoContent();

        self::assertSoftDeleted('inmueble_imagenes', ['id' => $image->id]);
    }

    public function test_reorder_normalizes_active_images_without_changing_principal(): void
    {
        $administrator = $this->user('admin-image-reorder@example.test', 'Administrador');
        $inmueble = $this->property('reorder');
        $first = $this->uploadRecord($administrator, $inmueble, 'first.jpg');
        $second = $this->uploadRecord($administrator, $inmueble, 'second.jpg');
        $third = $this->uploadRecord($administrator, $inmueble, 'third.jpg');

        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/reordenar', $administrator, [
            'imagenes' => [$third->id, $first->id, $second->id],
        ])->assertOk();

        self::assertSame(0, $third->fresh()->orden);
        self::assertSame(1, $first->fresh()->orden);
        self::assertSame(2, $second->fresh()->orden);
        self::assertTrue((bool) $first->fresh()->es_principal);
    }

    public function test_reorder_requires_exact_complete_active_image_set(): void
    {
        $administrator = $this->user('admin-image-reorder-validation@example.test', 'Administrador');
        $inmueble = $this->property('reorder-validation');
        $first = $this->uploadRecord($administrator, $inmueble);
        $second = $this->uploadRecord($administrator, $inmueble, 'second.jpg');

        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/reordenar', $administrator, [
            'imagenes' => [$first->id, $first->id],
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/reordenar', $administrator, [
            'imagenes' => [$first->id],
        ])->assertUnprocessable();
        $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/reordenar', $administrator, [
            'imagenes' => [$first->id, 999999],
        ])->assertUnprocessable();

        self::assertSame([0, 1], $inmueble->imagenes()->orderBy('orden')->pluck('orden')->all());
        self::assertNotNull($second->fresh());
    }

    public function test_reorder_rejects_internal_fields_without_changing_order(): void
    {
        $administrator = $this->user('admin-image-reorder-prohibited@example.test', 'Administrador');
        $inmueble = $this->property('reorder-prohibited');
        $first = $this->uploadRecord($administrator, $inmueble);
        $second = $this->uploadRecord($administrator, $inmueble, 'second.jpg');
        $expectedOrders = [
            $first->id => $first->fresh()->orden,
            $second->id => $second->fresh()->orden,
        ];

        foreach (['nombre_original', 'mime_type', 'tamano_bytes', 'texto_alternativo'] as $field) {
            $this->apiPatch('/api/v1/inmuebles/'.$inmueble->id.'/imagenes/reordenar', $administrator, [
                'imagenes' => [$second->id, $first->id],
                $field => 'forbidden',
            ])->assertUnprocessable();
        }

        foreach ($expectedOrders as $imageId => $order) {
            self::assertSame($order, InmuebleImagen::query()->findOrFail($imageId)->orden);
        }
    }

    public function test_reorder_rejects_an_image_from_another_property(): void
    {
        $administrator = $this->user('admin-image-reorder-foreign@example.test', 'Administrador');
        $first = $this->property('reorder-foreign-first');
        $second = $this->property('reorder-foreign-second');
        $ownImage = $this->uploadRecord($administrator, $first);
        $foreignImage = $this->uploadRecord($administrator, $second);

        $this->apiPatch('/api/v1/inmuebles/'.$first->id.'/imagenes/reordenar', $administrator, [
            'imagenes' => [$ownImage->id, $foreignImage->id],
        ])->assertUnprocessable();
    }

    public function test_upload_compensates_remote_object_when_database_insert_fails(): void
    {
        $administrator = $this->user('admin-image-compensation@example.test', 'Administrador');
        $inmueble = $this->property('compensation');
        $file = UploadedFile::fake()->image('compensation.jpg');
        $listener = static function (): void {
            throw new RuntimeException('Forced database persistence failure.');
        };
        InmuebleImagen::creating($listener);

        try {
            $exception = null;

            try {
                app(UploadInmuebleImagenAction::class)->execute($inmueble, $file);
            } catch (Throwable $thrown) {
                $exception = $thrown;
            }

            self::assertInstanceOf(RuntimeException::class, $exception);
            self::assertCount(1, $this->storage->uploads);
            self::assertSame([$this->storage->uploads[0]['path']], $this->storage->deletes);
            self::assertDatabaseCount('inmueble_imagenes', 0);
        } finally {
            InmuebleImagen::flushEventListeners();
        }
    }

    public function test_upload_failure_attempts_cleanup_and_preserves_original_exception(): void
    {
        $administrator = $this->user('admin-image-upload-failure@example.test', 'Administrador');
        $inmueble = $this->property('upload-failure');
        $originalException = new RuntimeException('Original upload failure.');
        $this->storage->failUploadAfterRecording = true;
        $this->storage->uploadException = $originalException;

        $exception = null;

        try {
            app(UploadInmuebleImagenAction::class)->execute(
                $inmueble,
                UploadedFile::fake()->image('upload-failure.jpg')
            );
        } catch (Throwable $thrown) {
            $exception = $thrown;
        }

        self::assertSame($originalException, $exception);
        self::assertCount(1, $this->storage->uploads);
        self::assertSame([$this->storage->uploads[0]['path']], $this->storage->deletes);
        self::assertDatabaseCount('inmueble_imagenes', 0);
    }

    public function test_upload_failure_and_cleanup_failure_preserve_original_exception(): void
    {
        $administrator = $this->user('admin-image-upload-cleanup-failure@example.test', 'Administrador');
        $inmueble = $this->property('upload-cleanup-failure');
        $originalException = new RuntimeException('Original upload failure.');
        $this->storage->failUploadAfterRecording = true;
        $this->storage->uploadException = $originalException;
        $this->storage->failDelete = true;

        $exception = null;

        try {
            app(UploadInmuebleImagenAction::class)->execute(
                $inmueble,
                UploadedFile::fake()->image('upload-cleanup-failure.jpg')
            );
        } catch (Throwable $thrown) {
            $exception = $thrown;
        }

        self::assertSame($originalException, $exception);
        self::assertCount(1, $this->storage->uploads);
        self::assertSame([$this->storage->uploads[0]['path']], $this->storage->deletes);
        self::assertDatabaseCount('inmueble_imagenes', 0);
    }

    public function test_fake_storage_treats_missing_remote_object_as_idempotent_delete(): void
    {
        $missingPath = 'inmuebles/999/missing.jpg';
        $this->storage->missingPaths[] = $missingPath;

        $this->storage->delete($missingPath);

        self::assertSame([$missingPath], $this->storage->deletes);
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Imagen',
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

    private function property(string $suffix): Inmueble
    {
        $owner = Propietario::create([
            'nombre_razon_social' => 'Owner '.$suffix,
            'rfc' => strtoupper(substr(md5('owner-'.$suffix), 0, 13)),
            'telefono' => '5555555555',
            'direccion' => 'Calle Imagen 1',
        ]);
        $category = Categoria::create(['nombre' => 'Category '.$suffix]);

        return Inmueble::create([
            'propietario_id' => $owner->id,
            'categoria_id' => $category->id,
            'codigo' => 'IMG-'.$suffix,
            'titulo' => 'Inmueble '.$suffix,
            'slug' => 'inmueble-'.$suffix,
            'descripcion' => 'Descripción de imagen',
            'tipo_operacion' => 'venta',
            'precio_venta' => '100000.00',
            'calle' => 'Calle Imagen 1',
            'colonia' => 'Centro',
            'municipio' => 'Municipio',
            'estado_ubicacion' => 'Estado',
            'codigo_postal' => '00000',
        ]);
    }

    private function assignProperty(Inmueble $inmueble, Agente $agente): void
    {
        AgenteInmueble::create([
            'agente_id' => $agente->id,
            'inmueble_id' => $inmueble->id,
            'es_principal' => false,
            'fecha_asignacion' => now(),
        ]);
    }

    private function interest(Cliente $cliente, Inmueble $inmueble): void
    {
        ClienteInmuebleInteres::create([
            'cliente_id' => $cliente->id,
            'inmueble_id' => $inmueble->id,
            'nivel_interes' => 'medio',
            'estado' => 'activo',
            'fecha_interes' => now(),
        ]);
    }

    private function upload(User $user, Inmueble $inmueble, string $filename, ?string $alt = null): TestResponse
    {
        return $this->uploadFile($user, $inmueble, UploadedFile::fake()->image($filename), $alt === null ? [] : [
            'texto_alternativo' => $alt,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function uploadFile(User $user, Inmueble $inmueble, UploadedFile $file, array $payload = []): TestResponse
    {
        Auth::forgetGuards();

        return $this->withHeaders(['Accept' => 'application/json'])
            ->actingAs($user, 'web')
            ->post('/api/v1/inmuebles/'.$inmueble->id.'/imagenes', array_merge([
                'imagen' => $file,
            ], $payload));
    }

    private function uploadRecord(User $user, Inmueble $inmueble, string $filename = 'image.jpg'): InmuebleImagen
    {
        $this->upload($user, $inmueble, $filename)->assertCreated();

        return InmuebleImagen::query()->latest('id')->firstOrFail();
    }

    private function apiGet(string $uri, User $user): TestResponse
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson($uri);
    }

    /** @param array<string, mixed> $payload */
    private function apiPatch(string $uri, User $user, array $payload = []): TestResponse
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
