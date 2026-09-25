<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ClienteAgentes\AssignAgenteToClienteAction;
use App\Actions\ClienteAgentes\RemoveAgenteFromClienteAction;
use App\Actions\ClienteAgentes\SetPrincipalClienteAgenteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteAgente\CreateClienteAgenteRequest;
use App\Http\Requests\ClienteAgente\SetPrincipalClienteAgenteRequest;
use App\Http\Resources\ClienteAgenteResource;
use App\Models\Cliente;
use App\Models\ClienteAgente;
use App\Queries\Visibility\VisibleClienteAgenteAssignmentsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClienteAgenteController extends Controller
{
    public function index(Cliente $cliente): AnonymousResourceCollection
    {
        Gate::authorize('view', $cliente);
        Gate::authorize('viewAny', ClienteAgente::class);

        $query = (new VisibleClienteAgenteAssignmentsQuery(request()->user(), $cliente))
            ->apply($cliente->asignacionesAgentes()->getQuery());

        $assignments = $query
            ->with([
                'agente:id,user_id,numero_empleado',
                'agente.user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado',
            ])
            ->orderByDesc('es_principal')
            ->orderBy('fecha_asignacion')
            ->orderBy('id')
            ->get();

        return ClienteAgenteResource::collection($assignments);
    }

    public function store(
        CreateClienteAgenteRequest $request,
        Cliente $cliente,
        AssignAgenteToClienteAction $action,
    ): JsonResponse {
        Gate::authorize('update', $cliente);
        Gate::authorize('create', ClienteAgente::class);

        return (new ClienteAgenteResource($action->execute($cliente, $request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function principal(
        SetPrincipalClienteAgenteRequest $request,
        Cliente $cliente,
        ClienteAgente $asignacion,
        SetPrincipalClienteAgenteAction $action,
    ): ClienteAgenteResource {
        Gate::authorize('update', $cliente);
        Gate::authorize('update', $asignacion);

        return new ClienteAgenteResource($action->execute($cliente, $asignacion));
    }

    public function destroy(
        Cliente $cliente,
        ClienteAgente $asignacion,
        RemoveAgenteFromClienteAction $action,
    ): Response {
        Gate::authorize('update', $cliente);
        Gate::authorize('delete', $asignacion);
        $action->execute($cliente, $asignacion);

        return response()->noContent();
    }
}
