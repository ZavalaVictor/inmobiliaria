<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\EstadoUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): AuthUserResource|JsonResponse
    {
        $credentials = [
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'estado' => EstadoUsuario::Activo->value,
        ];

        if (! Auth::guard('web')->attempt($credentials, false)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas no son válidas.',
            ], 401);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return new AuthUserResource($user);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(Request $request): AuthUserResource
    {
        return new AuthUserResource($request->user());
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink($request->validated());

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.',
        ], 202);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            return response()->json([
                'message' => 'El enlace de recuperación no es válido o ya expiró.',
            ], 422);
        }

        return response()->json([
            'message' => 'La contraseña fue restablecida correctamente.',
        ]);
    }
}
