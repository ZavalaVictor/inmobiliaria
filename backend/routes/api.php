<?php

use App\Http\Controllers\Api\V1\AgenteController;
use App\Http\Controllers\Api\V1\AgenteInmuebleController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CategoriaController;
use App\Http\Controllers\Api\V1\CitaController;
use App\Http\Controllers\Api\V1\CitaHistorialController;
use App\Http\Controllers\Api\V1\ClienteAgenteController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\ClienteInmuebleInteresController;
use App\Http\Controllers\Api\V1\InmuebleController;
use App\Http\Controllers\Api\V1\InmuebleImagenController;
use App\Http\Controllers\Api\V1\InteraccionClienteController;
use App\Http\Controllers\Api\V1\OperacionController;
use App\Http\Controllers\Api\V1\OportunidadController;
use App\Http\Controllers\Api\V1\OportunidadHistorialController;
use App\Http\Controllers\Api\V1\PropietarioController;
use App\Http\Controllers\Api\V1\PublicSolicitudInformacionController;
use App\Http\Controllers\Api\V1\SolicitudInformacionController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'inmobiliaria-api',
    ]);
});

Route::prefix('v1')->group(function (): void {
    Route::post('public/solicitudes', [PublicSolicitudInformacionController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth-forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth-reset-password');

        Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
        Route::apiResource('clientes', ClienteController::class);
        Route::apiResource('agentes', AgenteController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('inmuebles', InmuebleController::class);
        Route::apiResource('propietarios', PropietarioController::class);
        Route::patch('citas/{cita}/reprogramar', [CitaController::class, 'reprogramar']);
        Route::patch('citas/{cita}/estado', [CitaController::class, 'estado']);
        Route::get('citas/{cita}/historial', [CitaHistorialController::class, 'index']);
        Route::get('citas', [CitaController::class, 'index']);
        Route::post('citas', [CitaController::class, 'store']);
        Route::get('citas/{cita}', [CitaController::class, 'show']);
        Route::patch('citas/{cita}', [CitaController::class, 'update']);
        Route::delete('citas/{cita}', [CitaController::class, 'destroy']);
        Route::get('operaciones', [OperacionController::class, 'index']);
        Route::post('operaciones', [OperacionController::class, 'store']);
        Route::get('operaciones/{operacion}', [OperacionController::class, 'show']);
        Route::patch('operaciones/{operacion}', [OperacionController::class, 'update']);
        Route::delete('operaciones/{operacion}', [OperacionController::class, 'destroy']);
        Route::patch('oportunidades/{oportunidad}/etapa', [OportunidadController::class, 'etapa']);
        Route::patch('oportunidades/{oportunidad}/estado', [OportunidadController::class, 'estado']);
        Route::get('oportunidades/{oportunidad}/historial', [OportunidadHistorialController::class, 'index']);
        Route::get('oportunidades', [OportunidadController::class, 'index']);
        Route::post('oportunidades', [OportunidadController::class, 'store']);
        Route::get('oportunidades/{oportunidad}', [OportunidadController::class, 'show']);
        Route::patch('oportunidades/{oportunidad}', [OportunidadController::class, 'update']);
        Route::delete('oportunidades/{oportunidad}', [OportunidadController::class, 'destroy']);
        Route::get('solicitudes', [SolicitudInformacionController::class, 'index']);
        Route::post('solicitudes', [SolicitudInformacionController::class, 'store']);
        Route::get('solicitudes/{solicitud}', [SolicitudInformacionController::class, 'show']);
        Route::patch('solicitudes/{solicitud}', [SolicitudInformacionController::class, 'update']);
        Route::delete('solicitudes/{solicitud}', [SolicitudInformacionController::class, 'destroy']);
        Route::put('users/{user}/roles', [UserController::class, 'updateRoles']);
        Route::apiResource('users', UserController::class);

        Route::scopeBindings()->group(function (): void {
            Route::get('operaciones/{operacion}/agentes', [OperacionController::class, 'agents']);
            Route::post('operaciones/{operacion}/agentes', [OperacionController::class, 'storeAgent']);
            Route::patch('operaciones/{operacion}/agentes/{asignacion}/principal', [OperacionController::class, 'principal']);
            Route::delete('operaciones/{operacion}/agentes/{asignacion}', [OperacionController::class, 'destroyAgent']);

            Route::get('clientes/{cliente}/intereses', [ClienteInmuebleInteresController::class, 'index']);
            Route::post('clientes/{cliente}/intereses', [ClienteInmuebleInteresController::class, 'store']);
            Route::get('clientes/{cliente}/intereses/{interes}', [ClienteInmuebleInteresController::class, 'show']);
            Route::patch('clientes/{cliente}/intereses/{interes}', [ClienteInmuebleInteresController::class, 'update']);
            Route::delete('clientes/{cliente}/intereses/{interes}', [ClienteInmuebleInteresController::class, 'destroy']);

            Route::get('clientes/{cliente}/interacciones', [InteraccionClienteController::class, 'index']);
            Route::post('clientes/{cliente}/interacciones', [InteraccionClienteController::class, 'store']);
            Route::get('clientes/{cliente}/interacciones/{interaccion}', [InteraccionClienteController::class, 'show']);
            Route::patch('clientes/{cliente}/interacciones/{interaccion}', [InteraccionClienteController::class, 'update']);
            Route::delete('clientes/{cliente}/interacciones/{interaccion}', [InteraccionClienteController::class, 'destroy']);

            Route::get('clientes/{cliente}/agentes', [ClienteAgenteController::class, 'index']);
            Route::post('clientes/{cliente}/agentes', [ClienteAgenteController::class, 'store']);
            Route::patch('clientes/{cliente}/agentes/{asignacion}/principal', [ClienteAgenteController::class, 'principal']);
            Route::delete('clientes/{cliente}/agentes/{asignacion}', [ClienteAgenteController::class, 'destroy']);

            Route::get('inmuebles/{inmueble}/agentes', [AgenteInmuebleController::class, 'index']);
            Route::post('inmuebles/{inmueble}/agentes', [AgenteInmuebleController::class, 'store']);
            Route::patch('inmuebles/{inmueble}/agentes/{asignacion}/principal', [AgenteInmuebleController::class, 'principal']);
            Route::delete('inmuebles/{inmueble}/agentes/{asignacion}', [AgenteInmuebleController::class, 'destroy']);

            Route::get('inmuebles/{inmueble}/imagenes', [InmuebleImagenController::class, 'index']);
            Route::post('inmuebles/{inmueble}/imagenes', [InmuebleImagenController::class, 'store']);
            Route::patch('inmuebles/{inmueble}/imagenes/reordenar', [InmuebleImagenController::class, 'reorder']);
            Route::patch('inmuebles/{inmueble}/imagenes/{imagen}/principal', [InmuebleImagenController::class, 'principal']);
            Route::patch('inmuebles/{inmueble}/imagenes/{imagen}', [InmuebleImagenController::class, 'update']);
            Route::delete('inmuebles/{inmueble}/imagenes/{imagen}', [InmuebleImagenController::class, 'destroy']);
        });
    });
});
