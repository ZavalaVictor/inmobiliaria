<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bitacora\ExportBitacoraRequest;
use App\Http\Requests\Bitacora\IndexBitacoraRequest;
use App\Http\Resources\BitacoraDetailResource;
use App\Http\Resources\BitacoraResource;
use App\Models\Bitacora;
use App\Models\User;
use App\Queries\Bitacora\BitacoraIndexQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BitacoraController extends Controller
{
    public function index(IndexBitacoraRequest $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request->user(), 'bitacora.ver');

        $filters = $request->validated();
        $query = (new BitacoraIndexQuery($filters))->apply(
            Bitacora::query()->with('user:id,nombres,apellido_paterno,apellido_materno')
        );

        return BitacoraResource::collection(
            $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString()
        );
    }

    public function show(Bitacora $registro): BitacoraDetailResource
    {
        $this->authorizePermission(request()->user(), 'bitacora.ver');

        return new BitacoraDetailResource(
            $registro->load('user:id,nombres,apellido_paterno,apellido_materno')
        );
    }

    public function export(ExportBitacoraRequest $request): StreamedResponse
    {
        $this->authorizePermission($request->user(), 'bitacora.exportar');

        $filters = $request->validated();
        $query = (new BitacoraIndexQuery($filters))->apply(
            Bitacora::query()->with('user:id,nombres,apellido_paterno,apellido_materno')
        );

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'id',
                'user_id',
                'actor_nombre',
                'accion',
                'entidad',
                'entidad_id',
                'descripcion',
                'fecha_evento',
                'created_at',
            ]);

            foreach ($query->cursor() as $registro) {
                $actor = $registro->user;
                $actorName = $actor === null
                    ? ''
                    : trim(implode(' ', array_filter([
                        $actor->nombres,
                        $actor->apellido_paterno,
                        $actor->apellido_materno,
                    ])));

                fputcsv($handle, [
                    $registro->id,
                    $registro->user_id,
                    $actorName,
                    $registro->accion,
                    $registro->entidad,
                    $registro->entidad_id,
                    $registro->descripcion,
                    $registro->fecha_evento?->toISOString(),
                    $registro->created_at?->toISOString(),
                ]);
            }

            fclose($handle);
        }, 'bitacora-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
