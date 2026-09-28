<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Visualizaciones\RecordVisualizacionInmuebleAction;
use App\Enums\OrigenVisualizacionInmueble;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisualizacionInmueble\IndexVisualizacionInmuebleRequest;
use App\Http\Requests\VisualizacionInmueble\RecordVisualizacionInmuebleRequest;
use App\Http\Resources\VisualizacionInmuebleResource;
use App\Models\Inmueble;
use App\Models\VisualizacionInmueble;
use App\Queries\Visualizaciones\VisualizacionInmuebleIndexQuery;
use App\Support\Authorization\ActorScope;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class VisualizacionInmuebleController extends Controller
{
    public function index(IndexVisualizacionInmuebleRequest $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('visualizaciones.ver'), Response::HTTP_FORBIDDEN);

        $filters = $request->validated();
        $visualizaciones = (new VisualizacionInmuebleIndexQuery($filters))
            ->apply(VisualizacionInmueble::query())
            ->with(['inmueble:id,codigo,titulo,slug,publicado'])
            ->paginate((int) ($filters['per_page'] ?? 15));

        return VisualizacionInmuebleResource::collection($visualizaciones);
    }

    public function storePortal(
        RecordVisualizacionInmuebleRequest $request,
        Inmueble $inmueble,
        RecordVisualizacionInmuebleAction $action,
    ): Response {
        $user = $request->user();

        abort_unless(
            ActorScope::isClient($user) && ActorScope::clientId($user) !== null,
            Response::HTTP_FORBIDDEN,
        );

        abort_unless(
            $inmueble->interesesClientes()
                ->where('cliente_id', ActorScope::clientId($user))
                ->whereNull('deleted_at')
                ->exists(),
            Response::HTTP_FORBIDDEN,
        );

        $action->execute($inmueble, $request, OrigenVisualizacionInmueble::PortalCliente);

        return response()->noContent();
    }
}
