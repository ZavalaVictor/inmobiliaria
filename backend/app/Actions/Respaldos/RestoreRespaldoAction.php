<?php

namespace App\Actions\Respaldos;

use App\Contracts\BackupOperationLock;
use App\Contracts\BackupPrivateStorage;
use App\Contracts\DatabaseRestoreService;
use App\Contracts\RestoreOperationJournal;
use App\Exceptions\BackupLockUnavailableException;
use App\Exceptions\BackupRestoreException;
use App\Models\Respaldo;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestoreRespaldoAction
{
    public function __construct(
        private readonly BackupPrivateStorage $storage,
        private readonly DatabaseRestoreService $restoreService,
        private readonly BackupOperationLock $lock,
        private readonly RestoreOperationJournal $journal,
        private readonly BitacoraService $bitacora,
    ) {}

    public function execute(User $actor, Respaldo $respaldo): Respaldo
    {
        $this->validatePreconditions($respaldo);

        try {
            return $this->lock->execute(function () use ($actor, $respaldo): Respaldo {
                $respaldo = Respaldo::query()->findOrFail($respaldo->getKey());
                $this->validatePreconditions($respaldo);
                $backupMetadata = $this->backupMetadata($respaldo);
                $this->markInProgress($respaldo, $actor);

                $context = [
                    'respaldo_id' => $respaldo->id,
                    'nombre_archivo' => $respaldo->nombre_archivo,
                    'checksum' => $respaldo->checksum_sha256,
                    'actor_user_id' => $actor->id,
                ];
                try {
                    $this->journal->append('restore_started', $context);
                    $this->restoreService->restoreFrom($this->storage->absolutePath($respaldo->ruta_archivo));
                    if (! $this->reconnectAfterExternalProcess()) {
                        throw new BackupRestoreException('No fue posible reconectar la base de datos.');
                    }
                    $respaldo = Respaldo::query()->find($respaldo->getKey());
                    if ($respaldo === null) {
                        throw new BackupRestoreException('El registro del respaldo no existe después de restaurar.');
                    }
                    $restoredAt = now();
                    $respaldo->update($backupMetadata + [
                        'restaurado_por_user_id' => $actor->id,
                        'restaurado_at' => $restoredAt,
                        'estado_restauracion' => 'completada',
                        'mensaje_error' => null,
                    ]);
                    $this->bitacora->record(
                        $actor,
                        'restauracion_completada',
                        'respaldo',
                        $respaldo->id,
                        'Restauración completada.',
                        ['estado_restauracion' => 'en_proceso'],
                        ['estado_restauracion' => 'completada'],
                    );
                    $this->journal->append('restore_completed', $context + ['resultado' => 'completada']);

                    return $respaldo->fresh(['generadoPor', 'restauradoPor']);
                } catch (Throwable $exception) {
                    $databaseUsable = $this->reconnectAfterExternalProcess();
                    $message = $this->safeError($exception);
                    if ($databaseUsable) {
                        try {
                            $current = Respaldo::query()->find($respaldo->getKey());
                            if ($current !== null) {
                                $current->update([
                                    'restaurado_por_user_id' => $actor->id,
                                    'estado_restauracion' => 'fallida',
                                    'mensaje_error' => $message,
                                ]);
                                try {
                                    $this->bitacora->record(
                                        $actor,
                                        'restauracion_fallida',
                                        'respaldo',
                                        $current->id,
                                        'Restauración fallida.',
                                        ['estado_restauracion' => 'en_proceso'],
                                        ['estado_restauracion' => 'fallida'],
                                    );
                                } catch (Throwable) {
                                    report($exception);
                                }
                            }
                        } catch (Throwable $databaseException) {
                            report($databaseException);
                        }
                    }
                    try {
                        $this->journal->append('restore_failed', $context + ['resultado' => 'fallida']);
                    } catch (Throwable) {
                        report($exception);
                    }

                    throw new BackupRestoreException('No fue posible restaurar el respaldo.', 0, $exception);
                }
            });
        } catch (BackupLockUnavailableException $exception) {
            throw $exception;
        }
    }

    private function validatePreconditions(Respaldo $respaldo): void
    {
        if (! (bool) config('backup.restore_enabled', false)) {
            throw new BackupRestoreException('La restauración está deshabilitada.');
        }
        if ($respaldo->estado?->value !== 'completado' && $respaldo->estado !== 'completado') {
            throw new BackupRestoreException('Solo pueden restaurarse respaldos completados.');
        }
        if ($respaldo->estado_restauracion?->value === 'en_proceso') {
            throw new BackupRestoreException('El respaldo ya está siendo restaurado.');
        }
        if (! str_ends_with(strtolower($respaldo->nombre_archivo), '.sql')) {
            throw new BackupRestoreException('El formato del respaldo no es válido.');
        }
        if (! $this->storage->exists($respaldo->ruta_archivo)) {
            throw new BackupRestoreException('El archivo del respaldo no existe.');
        }
        if ($this->storage->size($respaldo->ruta_archivo) < 1) {
            throw new BackupRestoreException('El archivo del respaldo está vacío.');
        }
        if ($this->storage->checksum($respaldo->ruta_archivo) !== $respaldo->checksum_sha256) {
            throw new BackupRestoreException('La integridad del respaldo no coincide.');
        }
    }

    private function markInProgress(Respaldo $respaldo, User $actor): void
    {
        $respaldo->update([
            'restaurado_por_user_id' => $actor->id,
            'estado_restauracion' => 'en_proceso',
            'mensaje_error' => null,
        ]);
    }

    private function safeError(Throwable $exception): string
    {
        $message = preg_replace('/[\r\n\t]+/', ' ', $exception->getMessage()) ?: 'Error de restauración.';

        return mb_substr($message, 0, 1000);
    }

    /** @return array<string, mixed> */
    private function backupMetadata(Respaldo $respaldo): array
    {
        return [
            'tipo' => $respaldo->tipo,
            'nombre_archivo' => $respaldo->nombre_archivo,
            'ruta_archivo' => $respaldo->ruta_archivo,
            'tamano_bytes' => $respaldo->tamano_bytes,
            'checksum_sha256' => $respaldo->checksum_sha256,
            'estado' => 'completado',
            'fecha_inicio' => $respaldo->fecha_inicio,
            'fecha_finalizacion' => $respaldo->fecha_finalizacion,
        ];
    }

    protected function reconnectAfterExternalProcess(): bool
    {
        if (config('app.env') !== 'testing') {
            try {
                DB::reconnect();
            } catch (Throwable $exception) {
                report($exception);

                return false;
            }
        }

        return true;
    }
}
