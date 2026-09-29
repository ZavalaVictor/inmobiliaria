<?php

namespace App\Jobs;

use App\Contracts\BackupOperationLock;
use App\Contracts\BackupPrivateStorage;
use App\Contracts\DatabaseBackupService;
use App\Exceptions\BackupLockUnavailableException;
use App\Models\Respaldo;
use App\Services\BitacoraService;
use App\Services\Respaldos\RespaldoNotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateRespaldoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $respaldoId) {}

    public function handle(
        DatabaseBackupService $backupService,
        BackupPrivateStorage $storage,
        BackupOperationLock $lock,
        BitacoraService $bitacora,
        ?RespaldoNotificationDispatcher $notifications = null,
    ): void {
        try {
            $lock->execute(function () use ($backupService, $storage, $bitacora, $notifications): void {
                $respaldo = Respaldo::query()->find($this->respaldoId);
                if ($respaldo === null || ($respaldo->estado?->value ?? $respaldo->estado) !== 'pendiente') {
                    return;
                }

                $respaldo->update(['estado' => 'en_proceso']);
                $temporaryPath = $storage->temporaryPath();
                $finalized = false;
                $succeeded = false;

                try {
                    $backupService->dumpTo($temporaryPath);
                    if (! is_file($temporaryPath) || filesize($temporaryPath) < 1) {
                        throw new \RuntimeException('El dump generado está vacío.');
                    }

                    $storage->putFromPath($temporaryPath, $respaldo->ruta_archivo);
                    $finalized = true;
                    $size = $storage->size($respaldo->ruta_archivo);
                    $checksum = $storage->checksum($respaldo->ruta_archivo);

                    DB::transaction(function () use ($respaldo, $size, $checksum, $bitacora): void {
                        $before = ['estado' => 'en_proceso'];
                        $respaldo->update([
                            'tamano_bytes' => $size,
                            'checksum_sha256' => $checksum,
                            'estado' => 'completado',
                            'fecha_finalizacion' => now(),
                            'mensaje_error' => null,
                        ]);
                        $bitacora->record(
                            null,
                            'respaldo_completado',
                            'respaldo',
                            $respaldo->id,
                            'Respaldo completado.',
                            $before,
                            [
                                'tipo' => $respaldo->tipo,
                                'estado' => 'completado',
                                'tamano_bytes' => $size,
                                'checksum_sha256' => $checksum,
                            ],
                        );
                    });
                    $succeeded = true;
                } catch (Throwable $exception) {
                    try {
                        if ($finalized || $storage->exists($respaldo->ruta_archivo)) {
                            $storage->delete($respaldo->ruta_archivo);
                        }
                    } catch (Throwable) {
                        report($exception);
                    }
                    if (is_file($temporaryPath)) {
                        @unlink($temporaryPath);
                    }

                    $message = $this->safeError($exception);
                    $respaldo->update([
                        'estado' => 'fallido',
                        'fecha_finalizacion' => now(),
                        'mensaje_error' => $message,
                    ]);
                    try {
                        $bitacora->record(
                            null,
                            'respaldo_fallido',
                            'respaldo',
                            $respaldo->id,
                            'Respaldo fallido.',
                            ['estado' => 'en_proceso'],
                            ['estado' => 'fallido'],
                        );
                    } catch (Throwable) {
                        report($exception);
                    }
                } finally {
                    if (is_file($temporaryPath)) {
                        @unlink($temporaryPath);
                    }
                }

                if ($notifications !== null) {
                    try {
                        $respaldo->load('generadoPor');
                        if ($succeeded && ($respaldo->tipo?->value ?? $respaldo->tipo) === 'manual') {
                            $notifications->manualCompleted($respaldo);
                        } elseif (! $succeeded && ($respaldo->tipo?->value ?? $respaldo->tipo) === 'automatico') {
                            $notifications->automaticFailed($respaldo);
                        } elseif (! $succeeded) {
                            $notifications->manualFailed($respaldo);
                        }
                    } catch (Throwable $notificationException) {
                        report($notificationException);
                    }
                }
            });
        } catch (BackupLockUnavailableException) {
            // El registro permanece pendiente para un siguiente intento controlado.
        }
    }

    private function safeError(Throwable $exception): string
    {
        $message = preg_replace('/[\r\n\t]+/', ' ', $exception->getMessage()) ?: 'Error de generación.';

        return mb_substr($message, 0, 1000);
    }
}
