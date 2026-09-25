<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ClienteInmuebleIntereses\CreateClienteInmuebleInteresAction;
use App\Actions\ClienteInmuebleIntereses\DeleteClienteInmuebleInteresAction;
use App\Actions\ClienteInmuebleIntereses\UpdateClienteInmuebleInteresAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteInmuebleInteres\CreateClienteInmuebleInteresRequest;
use App\Http\Requests\ClienteInmuebleInteres\IndexClienteInmuebleInteresRequest;
use App\Http\Requests\ClienteInmuebleInteres\UpdateClienteInmuebleInteresRequest;
use App\Http\Resources\ClienteInmuebleInteresResource;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Queries\Visibility\VisibleClienteInmuebleInteresesQuery;
use App\Support\Authorization\ActorScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClienteInmuebleInteresController extends Controller
{
    public function index(
        IndexClienteInmuebleInteresRequest $request,
        Cliente $cliente,
    ): AnonymousResourceCollection {
        if (! ActorScope::isAgent(request()->user())) {
            Gate::authorize('view', $cliente);
        }

        Gate::authorize('viewAny', ClienteInmuebleInteres::class);

        $query = (new VisibleClienteInmuebleInteresesQuery(request()->user(), $cliente))
            ->apply($cliente->interesesInmuebles()->getQuery(), $request->validated());

        $interests = $query
            ->with('inmueble:id,codigo,titulo,slug')
            ->paginate((int) ($request->validated('per_page') ?? 15));

        return ClienteInmuebleInteresResource::collection($interests);
    }

    public function store(
        CreateClienteInmuebleInteresRequest $request,
        Cliente $cliente,
        CreateClienteInmuebleInteresAction $action,
    ): JsonResponse {
        Gate::authorize('update', $cliente);
        Gate::authorize('create', ClienteInmuebleInteres::class);

        return (new ClienteInmuebleInteresResource(
            $action->execute(request()->user(), $cliente, $request->validated())
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cliente $cliente, ClienteInmuebleInteres $interes): ClienteInmuebleInteresResource
    {
        Gate::authorize('view', $interes);

        return new ClienteInmuebleInteresResource(
            $interes->load('inmueble:id,codigo,titulo,slug')
        );
    }

    public function update(
        UpdateClienteInmuebleInteresRequest $request,
        Cliente $cliente,
        ClienteInmuebleInteres $interes,
        UpdateClienteInmuebleInteresAction $action,
    ): ClienteInmuebleInteresResource {
        Gate::authorize('update', $interes);

        return new ClienteInmuebleInteresResource($action->execute($interes, $request->validated()));
    }

    public function destroy(
        Cliente $cliente,
        ClienteInmuebleInteres $interes,
        DeleteClienteInmuebleInteresAction $action,
    ): Response {
        Gate::authorize('delete', $interes);
        $action->execute($interes);

        return response()->noContent();
    }
}
