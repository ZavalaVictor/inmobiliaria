<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoCorreo;
use App\Enums\EstadoUsuario;
use App\Enums\TipoCorreo;
use App\Mail\Clientes\ClientePortalActivationMail;
use App\Models\Cliente;
use App\Models\HistorialCorreo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ClientePortalAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
    }

    public function test_only_administrator_can_manage_the_portal(): void
    {
        $cliente = $this->client('Cliente objetivo', 'portal-admin@example.test');

        foreach (['Agente Inmobiliario', 'Asistente', 'Director General', 'Cliente'] as $role) {
            $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $this->user($role.'-enable@example.test', $role), [])->assertForbidden();
            $this->apiPatch('/api/v1/clientes/'.$cliente->id.'/portal/deshabilitar', $this->user($role.'-disable@example.test', $role), [])->assertForbidden();
        }

        Auth::forgetGuards();
        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/clientes/'.$cliente->id.'/portal/habilitar')
            ->assertUnauthorized();
    }

    public function test_admin_creates_exactly_one_client_user_and_records_activation(): void
    {
        $admin = $this->user('portal-create-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente Nuevo', 'Portal-Create@example.test');

        $response = $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])
            ->assertCreated()
            ->assertJsonPath('data.portal.habilitado', true)
            ->assertJsonPath('data.portal.estado_cuenta', 'activo')
            ->assertJsonPath('data.correo.estado', 'enviado')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.token');

        $user = User::query()->findOrFail($response->json('data.portal.user_id'));
        self::assertSame(['Cliente'], $user->getRoleNames()->sort()->values()->all());
        self::assertSame($user->id, $cliente->fresh()->user_id);
        self::assertSame('portal-create@example.test', $user->email);
        self::assertDatabaseHas('historial_correos', [
            'cliente_id' => $cliente->id,
            'destinatario_user_id' => $user->id,
            'enviado_por_user_id' => $admin->id,
            'tipo' => TipoCorreo::PortalClienteActivacion->value,
            'estado' => EstadoCorreo::Enviado->value,
        ]);
        self::assertStringNotContainsString('token', (string) HistorialCorreo::query()->latest('id')->value('mensaje_error'));
        Mail::assertSent(ClientePortalActivationMail::class);
        Auth::forgetGuards();
        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/clientes/'.$cliente->id)
            ->assertOk()
            ->assertJsonPath('data.portal.configurado', true)
            ->assertJsonPath('data.portal.habilitado', true)
            ->assertJsonPath('data.portal.user_id', $user->id);
    }

    public function test_admin_payload_cannot_control_portal_account_fields(): void
    {
        $admin = $this->user('portal-payload-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente Payload', 'portal-payload@example.test');

        $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [
            'user_id' => 999,
            'role' => 'Administrador',
            'password' => 'Password123',
        ])->assertUnprocessable();

        self::assertNull($cliente->fresh()->user_id);
        self::assertDatabaseMissing('users', ['email' => $cliente->email]);
        Mail::assertNothingSent();
    }

    public function test_invalid_email_and_existing_email_are_conflicts_without_side_effects(): void
    {
        $admin = $this->user('portal-conflict-admin@example.test', 'Administrador');
        $missingEmail = $this->client('Sin correo', null);
        $this->apiPost('/api/v1/clientes/'.$missingEmail->id.'/portal/habilitar', $admin, [])->assertUnprocessable();
        self::assertNull($missingEmail->fresh()->user_id);

        $existing = $this->user('portal-existing@example.test', 'Agente Inmobiliario');
        $unlinked = $this->client('Correo ocupado', $existing->email);
        $this->apiPost('/api/v1/clientes/'.$unlinked->id.'/portal/habilitar', $admin, [])->assertConflict();
        self::assertNull($unlinked->fresh()->user_id);
        self::assertSame(1, User::query()->where('email', $existing->email)->count());
        Mail::assertNothingSent();
    }

    public function test_activation_uses_the_real_password_broker_and_token_is_one_time(): void
    {
        $admin = $this->user('portal-reset-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente Reset', 'portal-reset@example.test');

        $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])->assertCreated();
        $mailable = Mail::sent(ClientePortalActivationMail::class)->first();
        self::assertNotNull($mailable);
        $html = $mailable->render();
        preg_match('/href="([^"]+)"/', $html, $matches);
        $url = html_entity_decode($matches[1] ?? '');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith('http://localhost:5173/nueva-contrasena?', $url);
        self::assertArrayHasKey('token', $query);
        self::assertArrayHasKey('email', $query);
        self::assertStringNotContainsString((string) $query['token'], (string) HistorialCorreo::query()->latest('id')->value('mensaje_error'));

        $payload = [
            'email' => $query['email'],
            'token' => $query['token'],
            'password' => 'NewPortalPassword123',
            'password_confirmation' => 'NewPortalPassword123',
        ];
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        self::assertTrue(Hash::check('NewPortalPassword123', Cliente::query()->findOrFail($cliente->id)->user->password));

        $this->prepareSpaSession();
        $this->spaPost('/api/v1/auth/login', [
            'email' => $query['email'],
            'password' => 'NewPortalPassword123',
        ])->assertOk();
        Auth::forgetGuards();
        $this->actingAs($cliente->fresh()->user, 'web')
            ->getJson('/api/v1/portal-cliente')
            ->assertOk()
            ->assertJsonPath('data.cliente.id', $cliente->id);
    }

    public function test_smtp_failure_keeps_portal_enabled_and_sanitizes_result(): void
    {
        $admin = $this->user('portal-smtp-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente SMTP', 'portal-smtp@example.test');
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('smtp password=hidden-secret'));

        $response = $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])
            ->assertCreated()
            ->assertJsonPath('data.portal.habilitado', true)
            ->assertJsonPath('data.correo.estado', 'fallido');

        $user = User::query()->findOrFail($response->json('data.portal.user_id'));
        self::assertSame($user->id, $cliente->fresh()->user_id);
        self::assertSame(['Cliente'], $user->getRoleNames()->sort()->values()->all());
        $history = HistorialCorreo::query()->latest('id')->firstOrFail();
        self::assertSame(EstadoCorreo::Fallido, $history->estado);
        self::assertStringNotContainsString('hidden-secret', (string) $history->mensaje_error);
        self::assertStringNotContainsString('password', strtolower((string) $history->mensaje_error));
        self::assertStringNotContainsString('smtp password', strtolower($response->getContent()));
    }

    public function test_enable_is_idempotent_and_reactivation_reuses_the_same_user(): void
    {
        $admin = $this->user('portal-idempotent-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente Reusable', 'portal-reusable@example.test');
        $first = $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])->assertCreated();
        $userId = $first->json('data.portal.user_id');
        $userCount = User::query()->count();

        $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])
            ->assertOk()
            ->assertJsonPath('data.portal.ya_habilitado', true);
        self::assertSame($userCount, User::query()->count());
        self::assertSame($userId, $cliente->fresh()->user_id);
        self::assertCount(1, HistorialCorreo::query()->get());

        User::query()->findOrFail($userId)->update(['estado' => EstadoUsuario::Inactivo]);
        $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])
            ->assertOk()
            ->assertJsonPath('data.portal.rehabilitado', true)
            ->assertJsonPath('data.portal.user_id', $userId);
        self::assertSame($userCount, User::query()->count());
        self::assertSame(EstadoUsuario::Activo, User::query()->findOrFail($userId)->estado);
        self::assertCount(1, HistorialCorreo::query()->get());
    }

    public function test_disable_preserves_link_history_and_is_idempotent(): void
    {
        $admin = $this->user('portal-disable-admin@example.test', 'Administrador');
        $cliente = $this->client('Cliente Deshabilitable', 'portal-disable@example.test');
        $created = $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])->assertCreated();
        $user = User::query()->findOrFail($created->json('data.portal.user_id'));

        $this->apiPatch('/api/v1/clientes/'.$cliente->id.'/portal/deshabilitar', $admin, [])
            ->assertOk()
            ->assertJsonPath('data.portal.habilitado', false)
            ->assertJsonPath('data.portal.user_id', $user->id);
        self::assertSame(EstadoUsuario::Inactivo, $user->fresh()->estado);
        self::assertSame($user->id, $cliente->fresh()->user_id);
        self::assertDatabaseHas('bitacora', ['accion' => 'cliente_portal_deshabilitado', 'entidad' => 'cliente', 'entidad_id' => $cliente->id]);

        $this->apiPatch('/api/v1/clientes/'.$cliente->id.'/portal/deshabilitar', $admin, [])->assertOk();
        self::assertSame(1, User::query()->whereKey($user->id)->count());
        self::assertSame(1, HistorialCorreo::query()->count());
    }

    public function test_internal_or_multi_role_linked_user_fails_closed(): void
    {
        $admin = $this->user('portal-role-admin@example.test', 'Administrador');
        $internal = $this->user('portal-role-internal@example.test', 'Administrador');
        $cliente = $this->client('Vínculo interno', $internal->email, $internal);

        $this->apiPost('/api/v1/clientes/'.$cliente->id.'/portal/habilitar', $admin, [])->assertConflict();
        self::assertSame(['Administrador'], $internal->fresh()->getRoleNames()->sort()->values()->all());
        self::assertSame($internal->id, $cliente->fresh()->user_id);
        Mail::assertNothingSent();
    }

    private function user(string $email, string $role): User
    {
        $user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Portal',
            'email' => $email,
            'password' => 'Password123',
            'estado' => EstadoUsuario::Activo->value,
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function client(string $name, ?string $email, ?User $user = null): Cliente
    {
        return Cliente::create([
            'user_id' => $user?->id,
            'nombres' => $name,
            'apellido_paterno' => 'Prueba',
            'email' => $email,
            'estado_cliente' => 'prospecto',
        ]);
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

    private function prepareSpaSession(): void
    {
        $response = $this->spaGet('/sanctum/csrf-cookie');
        $xsrfCookie = collect($response->headers->getCookies())
            ->first(static fn ($cookie): bool => $cookie->getName() === 'XSRF-TOKEN');

        $this->xsrfToken = $xsrfCookie === null ? null : urldecode($xsrfCookie->getValue());
    }

    /** @param array<string, mixed> $data */
    private function spaPost(string $uri, array $data): TestResponse
    {
        $response = $this->withCredentials()
            ->withHeaders(array_filter([
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
                'Accept' => 'application/json',
                'X-XSRF-TOKEN' => $this->xsrfToken,
            ]))
            ->postJson($uri, $data);

        $this->rememberResponseCookies($response);

        return $response;
    }

    private function spaGet(string $uri): TestResponse
    {
        $response = $this->withCredentials()
            ->withHeaders(array_filter([
                'Origin' => 'http://localhost:5173',
                'Referer' => 'http://localhost:5173/',
                'Accept' => 'application/json',
                'X-XSRF-TOKEN' => $this->xsrfToken,
            ]))
            ->getJson($uri);

        $this->rememberResponseCookies($response);

        return $response;
    }

    private function rememberResponseCookies(TestResponse $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }
    }

    private ?string $xsrfToken = null;
}
