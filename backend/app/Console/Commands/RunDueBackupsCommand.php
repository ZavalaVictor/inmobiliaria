<?php

namespace App\Console\Commands;

use App\Actions\Respaldos\CreateRespaldoAction;
use App\Enums\TipoRespaldo;
use App\Exceptions\MultipleBackupConfigurationsException;
use App\Services\ConfiguracionRespaldoResolver;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunDueBackupsCommand extends Command
{
    protected $signature = 'backups:run-due';

    protected $description = 'Programa el respaldo automático cuando corresponde.';

    public function handle(ConfiguracionRespaldoResolver $resolver, CreateRespaldoAction $create): int
    {
        try {
            $created = DB::transaction(function () use ($resolver, $create): bool {
                $configuration = $resolver->resolve(true);
                if ($configuration === null || ! $configuration->activo) {
                    return false;
                }

                $dueAt = $this->dueAt($configuration->frecuencia?->value ?? $configuration->frecuencia, $configuration->hora_ejecucion, $configuration->dia_semana, $configuration->dia_mes);
                if ($dueAt === null || ($configuration->ultima_ejecucion_at !== null && $configuration->ultima_ejecucion_at->greaterThanOrEqualTo($dueAt))) {
                    return false;
                }

                $configuration->update(['ultima_ejecucion_at' => now()]);
                $create->execute(null, TipoRespaldo::Automatico);

                return true;
            });
        } catch (MultipleBackupConfigurationsException $exception) {
            report($exception);

            return self::SUCCESS;
        }

        if ($created) {
            $this->info('Respaldo automático programado.');
        }

        return self::SUCCESS;
    }

    private function dueAt(string $frequency, string $time, ?int $dayOfWeek, ?int $dayOfMonth): ?CarbonImmutable
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $candidate = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d').' '.$time, config('app.timezone'));

        return match ($frequency) {
            'diario' => $now->greaterThanOrEqualTo($candidate) ? $candidate : null,
            'semanal' => $dayOfWeek === $now->isoWeekday() && $now->greaterThanOrEqualTo($candidate) ? $candidate : null,
            'mensual' => $dayOfMonth === $now->day && $now->greaterThanOrEqualTo($candidate) ? $candidate : null,
            default => null,
        };
    }
}
