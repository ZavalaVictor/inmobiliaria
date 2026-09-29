<?php

namespace App\Http\Resources;

use App\Services\ConfiguracionRespaldoResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionRespaldoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaults = app(ConfiguracionRespaldoResolver::class)->defaults();
        $model = $this->resource;

        return [
            'id' => $model?->id,
            'activo' => $model?->activo ?? $defaults['activo'],
            'frecuencia' => $model?->frecuencia?->value ?? $model?->frecuencia ?? $defaults['frecuencia'],
            'hora_ejecucion' => $model?->hora_ejecucion ?? $defaults['hora_ejecucion'],
            'dia_semana' => $model?->dia_semana ?? $defaults['dia_semana'],
            'dia_mes' => $model?->dia_mes ?? $defaults['dia_mes'],
            'retencion_dias' => $model?->retencion_dias ?? $defaults['retencion_dias'],
            'ultima_ejecucion_at' => $model?->ultima_ejecucion_at?->toISOString(),
            'updated_at' => $model?->updated_at?->toISOString(),
            'actualizado_por' => $this->when($model !== null && $model->relationLoaded('actualizadoPor'), function () use ($model): ?array {
                return $model->actualizadoPor === null ? null : [
                    'id' => $model->actualizadoPor->id,
                    'nombres' => $model->actualizadoPor->nombres,
                    'apellido_paterno' => $model->actualizadoPor->apellido_paterno,
                    'apellido_materno' => $model->actualizadoPor->apellido_materno,
                ];
            }),
        ];
    }
}
