<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Respaldos\CreateRespaldoAction;
use App\Actions\Respaldos\RestoreRespaldoAction;
use App\Contracts\BackupPrivateStorage;
use App\Exceptions\BackupLockUnavailableException;
use App\Exceptions\BackupRestoreException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Respaldo\CreateRespaldoRequest;
use App\Http\Requests\Respaldo\IndexRespaldoRequest;
use App\Http\Requests\Respaldo\RestoreRespaldoRequest;
use App\Http\Resources\RespaldoDetailResource;
use App\Http\Resources\RespaldoResource;
use App\Models\Respaldo;
use App\Models\User;
use App\Queries\Respaldos\RespaldoIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RespaldoController extends Controller
{
    public function index(IndexRespaldoRequest $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request->user(), 'respaldos.ver');
        $filters = $request->validated();
        $query = (new RespaldoIndexQuery($filters))->apply(
            Respaldo::query()->with([
                'generadoPor:id,nombres,apellido_paterno,apellido_materno',
                'restauradoPor:id,nombres,apellido_paterno,apellido_materno',
            ])
        );

        return RespaldoResource::collection(
            $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString()
        );
    }

    public function store(CreateRespaldoRequest $request, CreateRespaldoAction $action): JsonResponse
    {
        $this->authorizePermission($request->user(), 'respaldos.crear');
        $respaldo = $action->execute($request->user());

        return (new RespaldoResource($respaldo))
            ->additional(['message' => 'Respaldo programado correctamente.'])
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function show(Respaldo $respaldo): RespaldoDetailResource
    {
        $this->authorizePermission(request()->user(), 'respaldos.ver');

        return new RespaldoDetailResource($respaldo->load([
            'generadoPor:id,nombres,apellido_paterno,apellido_materno',
            'restauradoPor:id,nombres,apellido_paterno,apellido_materno',
        ]));
    }

    public function download(Respaldo $respaldo, BackupPrivateStorage $storage): StreamedResponse
    {
        $this->authorizePermission(request()->user(), 'respaldos.ver');
        if (($respaldo->estado?->value ?? $respaldo->estado) !== 'completado') {
            abort(Response::HTTP_NOT_FOUND);
        }
        if (! $storage->exists($respaldo->ruta_archivo)) {
            abort(Response::HTTP_NOT_FOUND);
        }
        if ($storage->size($respaldo->ruta_archivo) < 1) {
            abort(Response::HTTP_NOT_FOUND);
        }
        if ($storage->checksum($respaldo->ruta_archivo) !== $respaldo->checksum_sha256) {
            abort(Response::HTTP_CONFLICT, 'La integridad del respaldo no coincide.');
        }

        $stream = $storage->readStream($respaldo->ruta_archivo);
        $filename = Str::of($respaldo->nombre_archivo)->replaceMatches('/[^A-Za-z0-9._-]/', '_')->toString();

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Length' => (string) $storage->size($respaldo->ruta_archivo),
        ]);
    }

    public function restore(RestoreRespaldoRequest $request, Respaldo $respaldo, RestoreRespaldoAction $action): RespaldoDetailResource
    {
        $this->authorizePermission($request->user(), 'respaldos.restaurar');

        try {
            return new RespaldoDetailResource($action->execute($request->user(), $respaldo));
        } catch (BackupLockUnavailableException $exception) {
            abort(Response::HTTP_CONFLICT, $exception->getMessage());
        } catch (BackupRestoreException $exception) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
    }

    private function authorizePermission(?User $user, string $permission): void
    {
        abort_unless(
            $user !== null
                && $user->hasRole('Administrador')
                && $user->can($permission),
            Response::HTTP_FORBIDDEN
        );
    }
}
