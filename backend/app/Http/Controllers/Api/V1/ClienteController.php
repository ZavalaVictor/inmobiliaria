<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clientes\CreateClienteAction;
use App\Actions\Clientes\DeleteClienteAction;
use App\Actions\Clientes\UpdateClienteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\CreateClienteRequest;
use App\Http\Requests\Cliente\IndexClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Queries\Clientes\ClienteIndexQuery;
use App\Queries\Visibility\VisibleClientesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClienteController extends Controller
{
    public function index(IndexClienteRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Cliente::class);

        $query = (new VisibleClientesQuery($request->user()))->apply(Cliente::query());
        $query = (new ClienteIndexQuery($request->validated()))->apply($query);
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return ClienteResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateClienteRequest $request, CreateClienteAction $action): JsonResponse
    {
        Gate::authorize('create', Cliente::class);

        return (new ClienteResource($action->execute($request->user(), $request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cliente $cliente): ClienteResource
    {
        Gate::authorize('view', $cliente);

        return new ClienteResource($cliente->load('user'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente, UpdateClienteAction $action): ClienteResource
    {
        Gate::authorize('update', $cliente);

        return new ClienteResource($action->execute($request->user(), $cliente, $request->validated()));
    }

    public function destroy(Cliente $cliente, DeleteClienteAction $action): Response
    {
        Gate::authorize('delete', $cliente);
        $action->execute($cliente);

        return response()->noContent();
    }
}
