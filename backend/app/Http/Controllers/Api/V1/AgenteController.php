<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Agentes\CreateAgenteAction;
use App\Actions\Agentes\DeleteAgenteAction;
use App\Actions\Agentes\UpdateAgenteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agente\CreateAgenteRequest;
use App\Http\Requests\Agente\IndexAgenteRequest;
use App\Http\Requests\Agente\UpdateAgenteRequest;
use App\Http\Resources\AgenteResource;
use App\Models\Agente;
use App\Queries\Agentes\AgenteIndexQuery;
use App\Queries\Visibility\VisibleAgentesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AgenteController extends Controller
{
    public function index(IndexAgenteRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Agente::class);

        $query = (new VisibleAgentesQuery($request->user()))->apply(Agente::query());
        $query = (new AgenteIndexQuery($request->validated()))->apply($query);
        $query->with('user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado');
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return AgenteResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateAgenteRequest $request, CreateAgenteAction $action): JsonResponse
    {
        Gate::authorize('create', Agente::class);

        $agente = $action->execute($request->validated(), $request->user());
        $agente->load('user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado');

        return (new AgenteResource($agente))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Agente $agente): AgenteResource
    {
        Gate::authorize('view', $agente);
        $agente->load('user:id,nombres,apellido_paterno,apellido_materno,email,telefono,estado');

        return new AgenteResource($agente);
    }

    public function update(
        UpdateAgenteRequest $request,
        Agente $agente,
        UpdateAgenteAction $action
    ): AgenteResource {
        Gate::authorize('update', $agente);

        return new AgenteResource($action->execute($agente, $request->validated(), $request->user()));
    }

    public function destroy(Agente $agente, DeleteAgenteAction $action): Response
    {
        Gate::authorize('delete', $agente);
        $action->execute($agente, request()->user());

        return response()->noContent();
    }
}
