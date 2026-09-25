<?php

namespace App\Providers;

use App\Contracts\InmuebleImageStorage;
use App\Services\FirebaseInmuebleImageStorage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $this->app->bind(InmuebleImageStorage::class, function (): InmuebleImageStorage {
            return app(FirebaseInmuebleImageStorage::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return rtrim((string) config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
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
}
