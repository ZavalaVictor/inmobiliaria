<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BitacoraService
{
    public const ACTIONS = [
        'usuario_creado',
        'usuario_actualizado',
        'usuario_eliminado',
        'usuario_roles_actualizados',
        'agente_creado',
        'agente_actualizado',
        'agente_eliminado',
        'agente_inmueble_asignado',
        'agente_inmueble_principal_cambiado',
        'agente_inmueble_desasignado',
        'cliente_agente_asignado',
        'cliente_agente_principal_cambiado',
        'cliente_agente_desasignado',
        'documento_creado',
        'documento_actualizado',
        'documento_eliminado',
        'operacion_creada',
        'operacion_actualizada',
        'operacion_eliminada',
        'operacion_agente_asignado',
        'operacion_agente_principal_cambiado',
        'operacion_agente_desasignado',
        'respaldo_creado',
        'respaldo_completado',
        'respaldo_fallido',
        'configuracion_respaldo_actualizada',
        'restauracion_completada',
        'restauracion_fallida',
    ];

    public const ENTITIES = [
        'usuario',
        'agente',
        'agente_inmueble',
        'cliente_agente',
        'documento',
        'operacion',
        'operacion_agente',
        'respaldo',
        'configuracion_respaldo',
    ];

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'tokens',
        'access_token',
        'refresh_token',
        'api_token',
        'csrf',
        'xsrf',
        'cookie',
        'cookies',
        'session',
        'session_id',
        'secret',
        'secrets',
        'credential',
        'credentials',
        'firebase_credentials',
        'database_password',
        'firebase_path',
        'bucket',
        'archivo',
        'file',
        'binary',
        'contenido',
        'contents',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        ?User $actor,
        string $action,
        string $entity,
        ?int $entityId = null,
        ?string $description = null,
        ?array $before = null,
        ?array $after = null,
    ): Bitacora {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException('La acción de Bitácora no está permitida.');
        }

        if (! in_array($entity, self::ENTITIES, true)) {
            throw new InvalidArgumentException('La entidad de Bitácora no está permitida.');
        }

        $request = $this->request();
        $ip = $request?->ip();

        return Bitacora::create([
            'user_id' => $actor?->getKey(),
            'accion' => $action,
            'entidad' => $entity,
            'entidad_id' => $entityId,
            'descripcion' => $description,
            'datos_anteriores' => $this->sanitizeSnapshot($before),
            'datos_nuevos' => $this->sanitizeSnapshot($after),
            'ip_hash' => is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)
                ? hash_hmac('sha256', $ip, (string) config('app.key'))
                : null,
            'user_agent' => $request?->userAgent() === null
                ? null
                : mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private function sanitizeSnapshot(?array $snapshot): ?array
    {
        if ($snapshot === null) {
            return null;
        }

        return $this->sanitizeValue($snapshot);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $nestedValue) {
            $normalizedKey = strtolower(str_replace(['-', ' '], '_', (string) $key));

            if ($this->isSensitiveKey($normalizedKey)) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($nestedValue);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        if (in_array($key, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        return (bool) preg_match('/(^|_)(password|token|secret|credential|cookie|session|csrf|xsrf)(_|$)/', $key);
    }
}
