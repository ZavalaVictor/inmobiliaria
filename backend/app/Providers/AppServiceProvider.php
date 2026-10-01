<?php

namespace App\Providers;

use App\Contracts\BackupOperationLock;
use App\Contracts\BackupPrivateStorage;
use App\Contracts\BackupProcessRunner;
use App\Contracts\DatabaseBackupService;
use App\Contracts\DatabaseRestoreService;
use App\Contracts\DocumentoPrivateStorage;
use App\Contracts\InmuebleImageStorage;
use App\Contracts\RestoreOperationJournal;
use App\Services\FileBackupOperationLock;
use App\Services\FirebaseDocumentoPrivateStorage;
use App\Services\FirebaseInmuebleImageStorage;
use App\Services\JsonRestoreOperationJournal;
use App\Services\LocalBackupPrivateStorage;
use App\Services\LocalInmuebleImageStorage;
use App\Services\MariaDbBackupService;
use App\Services\MariaDbRestoreService;
use App\Services\SymfonyBackupProcessRunner;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BackupPrivateStorage::class, LocalBackupPrivateStorage::class);
        $this->app->bind(BackupProcessRunner::class, SymfonyBackupProcessRunner::class);
        $this->app->bind(DatabaseBackupService::class, MariaDbBackupService::class);
        $this->app->bind(DatabaseRestoreService::class, MariaDbRestoreService::class);
        $this->app->bind(BackupOperationLock::class, FileBackupOperationLock::class);
        $this->app->bind(RestoreOperationJournal::class, JsonRestoreOperationJournal::class);

        $this->app->bind(DocumentoPrivateStorage::class, function (): DocumentoPrivateStorage {
            return app(FirebaseDocumentoPrivateStorage::class);
        });

        $this->app->bind(InmuebleImageStorage::class, function (): InmuebleImageStorage {
            return (string) config('services.firebase.images_bucket') !== ''
                ? app(FirebaseInmuebleImageStorage::class)
                : app(LocalInmuebleImageStorage::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return $this->frontendPasswordResetUrl($user, $token);
        });

        ResetPassword::toMailUsing(function (object $user, string $token): MailMessage {
            $url = $this->frontendPasswordResetUrl($user, $token);

            return (new MailMessage)->view([
                'html' => 'emails.auth.password-reset',
                'text' => 'emails.auth.password-reset-text',
            ], [
                'url' => $url,
                'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ])->subject('Solicitud para restablecer tu contraseña');
        });

        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->authRateLimitKey($request));
        });

        RateLimiter::for('auth-forgot-password', function (Request $request): Limit {
            return Limit::perMinute(3)->by($this->authRateLimitKey($request));
        });

        RateLimiter::for('auth-reset-password', function (Request $request): Limit {
            return Limit::perMinute(5)->by($this->authRateLimitKey($request));
        });
    }

    private function authRateLimitKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    private function frontendPasswordResetUrl(object $user, string $token): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/nueva-contrasena?'.http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
