<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Operaciones\AssignAgenteToOperacionAction;
use App\Actions\Operaciones\CreateOperacionAction;
use App\Actions\Operaciones\DeleteOperacionAction;
use App\Actions\Operaciones\RemoveAgenteFromOperacionAction;
use App\Actions\Operaciones\SetPrincipalOperacionAgenteAction;
use App\Actions\Operaciones\UpdateOperacionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operacion\CreateOperacionAgenteRequest;
use App\Http\Requests\Operacion\CreateOperacionRequest;
use App\Http\Requests\Operacion\IndexOperacionRequest;
use App\Http\Requests\Operacion\SetPrincipalOperacionAgenteRequest;
use App\Http\Requests\Operacion\UpdateOperacionRequest;
use App\Http\Resources\OperacionAgenteResource;
use App\Http\Resources\OperacionResource;
use App\Models\Operacion;
use App\Models\OperacionAgente;
use App\Queries\Operaciones\OperacionIndexQuery;
use App\Queries\Visibility\VisibleOperacionesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OperacionController extends Controller
{
    public function index(IndexOperacionRequest $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user())->authorize('viewAny', Operacion::class);

        $filters = $request->validated();
        $query = (new VisibleOperacionesQuery($request->user()))->apply(Operacion::query());
        $query = (new OperacionIndexQuery($filters))->apply($query);

        return OperacionResource::collection(
            $query->with($this->relations())
                ->paginate((int) ($filters['per_page'] ?? 15))
                ->withQueryString()
        );
    }

    public function store(CreateOperacionRequest $request, CreateOperacionAction $action): JsonResponse
    {
        Gate::forUser($request->user())->authorize('create', Operacion::class);

        return (new OperacionResource($action->execute($request->user(), $request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Operacion $operacion): OperacionResource
    {
        Gate::authorize('view', $operacion);

        return new OperacionResource($operacion->load($this->relations()));
    }

    public function update(UpdateOperacionRequest $request, Operacion $operacion, UpdateOperacionAction $action): OperacionResource
    {
        Gate::authorize('update', $operacion);

        return new OperacionResource($action->execute($request->user(), $operacion, $request->validated()));
    }

    public function destroy(Operacion $operacion, DeleteOperacionAction $action): Response
    {
        Gate::authorize('delete', $operacion);
        $action->execute($operacion, request()->user());

        return response()->noContent();
    }

    public function agents(Operacion $operacion): AnonymousResourceCollection
    {
        Gate::authorize('view', $operacion);

        $assignments = $operacion->asignacionesAgentes()
            ->whereHas('agente')
            ->with([
                'agente:id,numero_empleado,user_id',
                'agente.user:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->orderByDesc('es_principal')
            ->orderBy('id')
            ->get();

        return OperacionAgenteResource::collection($assignments);
    }

    public function storeAgent(
        CreateOperacionAgenteRequest $request,
        Operacion $operacion,
        AssignAgenteToOperacionAction $action,
    ): JsonResponse {
        Gate::authorize('update', $operacion);

        return (new OperacionAgenteResource($action->execute($operacion, $request->validated(), $request->user())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function principal(
        SetPrincipalOperacionAgenteRequest $request,
        Operacion $operacion,
        OperacionAgente $asignacion,
        SetPrincipalOperacionAgenteAction $action,
    ): OperacionAgenteResource {
        Gate::authorize('update', $operacion);

        return new OperacionAgenteResource($action->execute($operacion, $asignacion, request()->user()));
    }

    public function destroyAgent(
        Operacion $operacion,
        OperacionAgente $asignacion,
        RemoveAgenteFromOperacionAction $action,
    ): Response {
        Gate::authorize('update', $operacion);
        $action->execute($operacion, $asignacion, request()->user());

        return response()->noContent();
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'registradoPor:id,nombres,apellido_paterno,apellido_materno',
            'asignacionesAgentes.agente:id,numero_empleado,user_id',
            'asignacionesAgentes.agente.user:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
