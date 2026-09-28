<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Solicitudes\CreateSolicitudInformacionAction;
use App\Actions\Solicitudes\DeleteSolicitudInformacionAction;
use App\Actions\Solicitudes\UpdateSolicitudInformacionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudInformacion\CreateSolicitudInformacionRequest;
use App\Http\Requests\SolicitudInformacion\IndexSolicitudInformacionRequest;
use App\Http\Requests\SolicitudInformacion\UpdateSolicitudInformacionRequest;
use App\Http\Resources\SolicitudInformacionResource;
use App\Models\SolicitudInformacion;
use App\Queries\Solicitudes\SolicitudIndexQuery;
use App\Queries\Visibility\VisibleSolicitudesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SolicitudInformacionController extends Controller
{
    public function index(IndexSolicitudInformacionRequest $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user())->authorize('viewAny', SolicitudInformacion::class);

        $filters = $request->validated();
        $query = (new VisibleSolicitudesQuery($request->user()))
            ->apply(SolicitudInformacion::query());
        $query = (new SolicitudIndexQuery($filters))->apply($query);

        $solicitudes = $query
            ->with([
                'cliente:id,nombres,apellido_paterno,apellido_materno',
                'inmueble:id,codigo,titulo,slug,publicado',
                'atendidaPor:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return SolicitudInformacionResource::collection($solicitudes);
    }

    public function store(
        CreateSolicitudInformacionRequest $request,
        CreateSolicitudInformacionAction $action,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('create', SolicitudInformacion::class);

        return (new SolicitudInformacionResource(
            $action->execute($request->user(), $request->validated())
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(SolicitudInformacion $solicitud): SolicitudInformacionResource
    {
        Gate::forUser(request()->user())->authorize('view', $solicitud);

        return new SolicitudInformacionResource($solicitud->load([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'atendidaPor:id,nombres,apellido_paterno,apellido_materno',
        ]));
    }

    public function update(
        UpdateSolicitudInformacionRequest $request,
        SolicitudInformacion $solicitud,
        UpdateSolicitudInformacionAction $action,
    ): SolicitudInformacionResource {
        Gate::forUser($request->user())->authorize('update', $solicitud);

        return new SolicitudInformacionResource(
            $action->execute($request->user(), $solicitud, $request->validated())
        );
    }

    public function destroy(
        SolicitudInformacion $solicitud,
        DeleteSolicitudInformacionAction $action,
    ): Response {
        Gate::forUser(request()->user())->authorize('delete', $solicitud);
        $action->execute($solicitud);

        return response()->noContent();
    }
}
