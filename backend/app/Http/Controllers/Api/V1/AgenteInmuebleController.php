<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AgenteInmuebles\AssignAgenteToInmuebleAction;
use App\Actions\AgenteInmuebles\RemoveAgenteFromInmuebleAction;
use App\Actions\AgenteInmuebles\SetPrincipalAgenteInmuebleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgenteInmueble\CreateAgenteInmuebleRequest;
use App\Http\Requests\AgenteInmueble\SetPrincipalAgenteInmuebleRequest;
use App\Http\Resources\AgenteInmuebleResource;
use App\Models\AgenteInmueble;
use App\Models\Inmueble;
use App\Queries\Visibility\VisibleAgenteInmuebleAssignmentsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AgenteInmuebleController extends Controller
{
    public function index(Inmueble $inmueble): AnonymousResourceCollection
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('viewAny', AgenteInmueble::class);

        $query = (new VisibleAgenteInmuebleAssignmentsQuery(request()->user(), $inmueble))
            ->apply($inmueble->asignacionesAgentes()->getQuery());

        $assignments = $query
            ->with([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ])
            ->orderByDesc('es_principal')
            ->orderBy('fecha_asignacion')
            ->orderBy('id')
            ->get();

        return AgenteInmuebleResource::collection($assignments);
    }

    public function store(
        CreateAgenteInmuebleRequest $request,
        Inmueble $inmueble,
        AssignAgenteToInmuebleAction $action,
    ): JsonResponse {
        Gate::authorize('update', $inmueble);
        Gate::authorize('create', AgenteInmueble::class);

        return (new AgenteInmuebleResource($action->execute($inmueble, $request->validated(), $request->user())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function principal(
        SetPrincipalAgenteInmuebleRequest $request,
        Inmueble $inmueble,
        AgenteInmueble $asignacion,
        SetPrincipalAgenteInmuebleAction $action,
    ): AgenteInmuebleResource {
        Gate::authorize('update', $inmueble);
        Gate::authorize('update', $asignacion);

        return new AgenteInmuebleResource($action->execute($inmueble, $asignacion, request()->user()));
    }

    public function destroy(
        Inmueble $inmueble,
        AgenteInmueble $asignacion,
        RemoveAgenteFromInmuebleAction $action,
    ): Response {
        Gate::authorize('update', $inmueble);
        Gate::authorize('delete', $asignacion);
        $action->execute($inmueble, $asignacion, request()->user());

        return response()->noContent();
    }
}
