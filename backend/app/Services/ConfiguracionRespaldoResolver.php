<?php

namespace App\Services;

use App\Exceptions\MultipleBackupConfigurationsException;
use App\Models\ConfiguracionRespaldo;

class ConfiguracionRespaldoResolver
{
    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'id' => null,
            'actualizado_por_user_id' => null,
            'activo' => false,
            'frecuencia' => 'diario',
            'hora_ejecucion' => '02:00:00',
            'dia_semana' => null,
            'dia_mes' => null,
            'retencion_dias' => 30,
            'ultima_ejecucion_at' => null,
            'updated_at' => null,
        ];
    }

    public function resolve(bool $lock = false): ?ConfiguracionRespaldo
    {
        $query = ConfiguracionRespaldo::query()->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        $rows = $query->get();
        if ($rows->count() > 1) {
            throw new MultipleBackupConfigurationsException('La configuración de respaldos es ambigua.');
        }

        return $rows->first();
    }
}
