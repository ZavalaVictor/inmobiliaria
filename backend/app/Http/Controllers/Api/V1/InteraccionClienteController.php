<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\InteraccionesClientes\CreateInteraccionClienteAction;
use App\Actions\InteraccionesClientes\DeleteInteraccionClienteAction;
use App\Actions\InteraccionesClientes\UpdateInteraccionClienteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\InteraccionCliente\CreateInteraccionClienteRequest;
use App\Http\Requests\InteraccionCliente\IndexInteraccionClienteRequest;
use App\Http\Requests\InteraccionCliente\UpdateInteraccionClienteRequest;
use App\Http\Resources\InteraccionClienteResource;
use App\Models\Cliente;
use App\Models\InteraccionCliente;
use App\Queries\Visibility\VisibleInteraccionesClienteQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InteraccionClienteController extends Controller
{
    public function index(
        IndexInteraccionClienteRequest $request,
        Cliente $cliente,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $cliente);
        Gate::authorize('viewAny', InteraccionCliente::class);

        $filters = $request->validated();
        $query = (new VisibleInteraccionesClienteQuery(request()->user(), $cliente))
            ->apply($cliente->interacciones()->getQuery(), $filters);

        $interacciones = $query
            ->with([
                'cliente:id,nombres,apellido_paterno,apellido_materno',
                'registradoPor:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->paginate((int) ($filters['per_page'] ?? 15));

        return InteraccionClienteResource::collection($interacciones);
    }

    public function store(
        CreateInteraccionClienteRequest $request,
        Cliente $cliente,
        CreateInteraccionClienteAction $action,
    ): JsonResponse {
        Gate::authorize('update', $cliente);
        Gate::authorize('create', InteraccionCliente::class);

        return (new InteraccionClienteResource(
            $action->execute(request()->user(), $cliente, $request->validated())
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cliente $cliente, InteraccionCliente $interaccion): InteraccionClienteResource
    {
        Gate::authorize('view', $interaccion);

        return new InteraccionClienteResource($interaccion->load([
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
        ]));
    }

    public function update(
        UpdateInteraccionClienteRequest $request,
        Cliente $cliente,
        InteraccionCliente $interaccion,
        UpdateInteraccionClienteAction $action,
    ): InteraccionClienteResource {
        Gate::authorize('update', $interaccion);

        return new InteraccionClienteResource($action->execute($interaccion, $request->validated()));
    }

    public function destroy(
        Cliente $cliente,
        InteraccionCliente $interaccion,
        DeleteInteraccionClienteAction $action,
    ): Response {
        Gate::authorize('delete', $interaccion);
        $action->execute($interaccion);

        return response()->noContent();
    }
}
