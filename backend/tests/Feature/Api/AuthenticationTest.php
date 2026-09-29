<?php

namespace Tests\Feature\Api;

use App\Enums\EstadoUsuario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::create([
            'nombres' => 'Usuario',
            'apellido_paterno' => 'Activo',
            'apellido_materno' => null,
            'email' => 'usuario@example.test',
            'password' => 'Password123',
            'estado' => EstadoUsuario::Activo,
        ]);

        $this->user->assignRole('Cliente');
    }

    public function test_active_user_can_login(): void
    {
        $this->loginResponse()
            ->assertOk()
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $this->assertAuthenticatedAs($this->user, 'web');
    }

    public function test_invalid_password_has_generic_response(): void
    {
        $this->spaPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'WrongPassword123',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Las credenciales proporcionadas no son válidas.');
    }

    public function test_pending_blocked_and_inactive_users_cannot_login(): void
    {
        foreach ([
            EstadoUsuario::Pendiente,
            EstadoUsuario::Bloqueado,
            EstadoUsuario::Inactivo,
        ] as $state) {
            $this->user->update(['estado' => $state]);

            $this->spaPost('/api/v1/auth/login', [
                'email' => $this->user->email,
                'password' => 'Password123',
            ])->assertUnauthorized()
                ->assertJsonPath('message', 'Las credenciales proporcionadas no son válidas.');

            $this->assertGuest('web');
        }
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $this->user->delete();

        $this->loginResponse()->assertUnauthorized();
        $this->assertGuest('web');
    }

    public function test_login_regenerates_the_session(): void
    {
        $this->startSession();
        $sessionId = $this->app['session']->getId();

        $this->prepareSpaSession();
        $this->spaPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'Password123',
        ])->assertOk();

        self::assertNotSame($sessionId, $this->app['session']->getId());
    }

    public function test_authenticated_user_can_read_me(): void
    {
        $this->actingAs($this->user, 'web');

        $this->spaGet('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.roles.0', 'Cliente')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_me_requires_authentication(): void
    {
        $this->spaGet('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_effective_permissions(): void
    {
        $this->actingAs($this->user, 'web');

        $this->spaGet('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonFragment(['clientes.ver'])
            ->assertJsonFragment(['dashboard.ver']);
    }

    public function test_user_can_logout(): void
    {
        $this->loginResponse()->assertOk();
        $this->spaPost('/api/v1/auth/logout')->assertOk();

        Auth::forgetGuards();
        $this->assertGuest('web');
    }

    public function test_blocked_user_is_logged_out_on_next_authenticated_request(): void
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['estado' => EstadoUsuario::Bloqueado]);

        $this->spaGet('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');

        $this->assertGuest('web');
    }

    public function test_forgot_password_response_is_generic_for_existing_and_unknown_email(): void
    {
        Notification::fake();

        $existing = $this->spaPost('/api/v1/auth/forgot-password', [
            'email' => $this->user->email,
        ]);
        $unknown = $this->spaPost('/api/v1/auth/forgot-password', [
            'email' => 'unknown@example.test',
        ]);

        $existing->assertAccepted();
        $unknown->assertAccepted();
        self::assertSame($existing->json('message'), $unknown->json('message'));
    }

    public function test_forgot_password_notification_uses_the_frontend_reset_url(): void
    {
        Notification::fake();

        $this->spaPost('/api/v1/auth/forgot-password', [
            'email' => $this->user->email,
        ])->assertAccepted();

        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification): bool {
            $mail = $notification->toMail($this->user);
            $url = $mail->viewData['url'] ?? null;

            return $mail->view === [
                'html' => 'emails.auth.password-reset',
                'text' => 'emails.auth.password-reset-text',
            ]
                && is_string($url)
                && str_starts_with($url, 'http://localhost:5173/nueva-contrasena?')
                && str_contains($url, 'email=usuario%40example.test')
                && str_contains($url, 'token=');
        });
    }

    public function test_valid_reset_token_changes_password(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->spaPost('/api/v1/auth/reset-password', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        self::assertTrue(Hash::check('NewPassword123', $this->user->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $this->spaPost('/api/v1/auth/reset-password', [
            'email' => $this->user->email,
            'token' => 'invalid-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertUnprocessable();
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $token = Password::broker()->createToken($this->user);
        $payload = [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ];

        $this->spaPost('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->spaPost('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_csrf_cookie_endpoint_is_the_official_sanctum_endpoint(): void
    {
        $this->spaGet('/sanctum/csrf-cookie')->assertNoContent();
    }

    public function test_session_expires_after_fifteen_minutes_of_inactivity(): void
    {
        $this->loginResponse()->assertOk();
        $this->travel(16)->minutes();
        Auth::forgetGuards();
        $this->flushSession();

        $this->spaGet('/api/v1/auth/me')->assertUnauthorized();
    }

    private function loginResponse(): TestResponse
    {
        $this->prepareSpaSession();

        return $this->spaPost('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'Password123',
        ]);
    }

    private function spaGet(string $uri): TestResponse
    {
        $response = $this->withCredentials()
            ->withHeaders($this->spaHeaders())
            ->getJson($uri);

        $this->rememberResponseCookies($response);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function spaPost(string $uri, array $data = []): TestResponse
    {
        if ($this->xsrfToken === null) {
            $this->prepareSpaSession();
        }

        $response = $this->withCredentials()
            ->withHeaders($this->spaHeaders())
            ->postJson($uri, $data);

        $this->rememberResponseCookies($response);

        return $response;
    }

    private function prepareSpaSession(): void
    {
        $response = $this->spaGet('/sanctum/csrf-cookie');
        $xsrfCookie = collect($response->headers->getCookies())
            ->first(static fn ($cookie): bool => $cookie->getName() === 'XSRF-TOKEN');

        $this->xsrfToken = $xsrfCookie === null
            ? null
            : urldecode($xsrfCookie->getValue());
    }

    /**
     * @return array<string, string>
     */
    private function spaHeaders(): array
    {
        return array_filter([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'Accept' => 'application/json',
            'X-XSRF-TOKEN' => $this->xsrfToken,
        ]);
    }

    private function rememberResponseCookies(TestResponse $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        }
    }

    private ?string $xsrfToken = null;
}
