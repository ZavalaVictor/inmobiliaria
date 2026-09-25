<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Propietarios\CreatePropietarioAction;
use App\Actions\Propietarios\DeletePropietarioAction;
use App\Actions\Propietarios\UpdatePropietarioAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Propietario\CreatePropietarioRequest;
use App\Http\Requests\Propietario\IndexPropietarioRequest;
use App\Http\Requests\Propietario\UpdatePropietarioRequest;
use App\Http\Resources\PropietarioResource;
use App\Models\Propietario;
use App\Queries\Propietarios\PropietarioIndexQuery;
use App\Queries\Visibility\VisiblePropietariosQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PropietarioController extends Controller
{
    public function index(IndexPropietarioRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Propietario::class);

        $query = (new VisiblePropietariosQuery($request->user()))->apply(Propietario::query());
        $query = (new PropietarioIndexQuery($request->validated()))->apply($query);
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return PropietarioResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreatePropietarioRequest $request, CreatePropietarioAction $action): JsonResponse
    {
        Gate::authorize('create', Propietario::class);

        return (new PropietarioResource($action->execute($request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Propietario $propietario): PropietarioResource
    {
        Gate::authorize('view', $propietario);

        return new PropietarioResource($propietario);
    }

    public function update(
        UpdatePropietarioRequest $request,
        Propietario $propietario,
        UpdatePropietarioAction $action
    ): PropietarioResource {
        Gate::authorize('update', $propietario);

        return new PropietarioResource($action->execute($propietario, $request->validated()));
    }

    public function destroy(Propietario $propietario, DeletePropietarioAction $action): Response
    {
        Gate::authorize('delete', $propietario);
        $action->execute($propietario);

        return response()->noContent();
    }
}
