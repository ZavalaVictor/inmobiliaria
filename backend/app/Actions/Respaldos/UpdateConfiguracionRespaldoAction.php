<?php

namespace App\Actions\Respaldos;

use App\Enums\FrecuenciaRespaldo;
use App\Models\ConfiguracionRespaldo;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\ConfiguracionRespaldoResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateConfiguracionRespaldoAction
{
    public function __construct(
        private readonly ConfiguracionRespaldoResolver $resolver,
        private readonly BitacoraService $bitacora,
    ) {}

    public function execute(User $actor, array $attributes): ConfiguracionRespaldo
    {
        $configuration = DB::transaction(function () use ($actor, $attributes): ConfiguracionRespaldo {
            $configuration = $this->resolver->resolve(true);
            $values = array_replace($this->resolver->defaults(), $configuration?->toArray() ?? [], $attributes);
            $this->validateCombination($values);

            $before = $configuration === null ? null : $this->snapshot($configuration);
            if ($configuration === null) {
                $configuration = new ConfiguracionRespaldo;
            }

            $configuration->fill([
                'actualizado_por_user_id' => $actor->getKey(),
                'activo' => (bool) $values['activo'],
                'frecuencia' => $values['frecuencia'],
                'hora_ejecucion' => $values['hora_ejecucion'],
                'dia_semana' => $values['dia_semana'],
                'dia_mes' => $values['dia_mes'],
                'retencion_dias' => (int) $values['retencion_dias'],
            ]);
            $configuration->save();

            $this->bitacora->record(
                $actor,
                'configuracion_respaldo_actualizada',
                'configuracion_respaldo',
                $configuration->getKey(),
                'Configuración de respaldos actualizada.',
                $before,
                $this->snapshot($configuration),
            );

            return $configuration;
        });

        return $configuration->fresh(['actualizadoPor:id,nombres,apellido_paterno,apellido_materno']);
    }

    /** @param array<string, mixed> $values */
    private function validateCombination(array $values): void
    {
        $frequency = $values['frecuencia'] instanceof FrecuenciaRespaldo
            ? $values['frecuencia']->value
            : (string) $values['frecuencia'];

        $valid = match ($frequency) {
            'diario' => $values['dia_semana'] === null && $values['dia_mes'] === null,
            'semanal' => $values['dia_semana'] !== null && $values['dia_mes'] === null,
            'mensual' => $values['dia_semana'] === null && $values['dia_mes'] !== null,
            default => false,
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'frecuencia' => ['La frecuencia no coincide con los días configurados.'],
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(ConfiguracionRespaldo $configuration): array
    {
        return [
            'activo' => $configuration->activo,
            'frecuencia' => $configuration->frecuencia,
            'hora_ejecucion' => $configuration->hora_ejecucion,
            'dia_semana' => $configuration->dia_semana,
            'dia_mes' => $configuration->dia_mes,
            'retencion_dias' => $configuration->retencion_dias,
        ];
    }
}
