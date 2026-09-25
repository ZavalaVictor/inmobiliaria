<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Inmuebles\CreateInmuebleAction;
use App\Actions\Inmuebles\DeleteInmuebleAction;
use App\Actions\Inmuebles\UpdateInmuebleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inmueble\CreateInmuebleRequest;
use App\Http\Requests\Inmueble\IndexInmuebleRequest;
use App\Http\Requests\Inmueble\UpdateInmuebleRequest;
use App\Http\Resources\InmuebleResource;
use App\Models\Inmueble;
use App\Queries\Inmuebles\InmuebleIndexQuery;
use App\Queries\Visibility\VisibleInmueblesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InmuebleController extends Controller
{
    public function index(IndexInmuebleRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Inmueble::class);

        $query = (new VisibleInmueblesQuery($request->user()))->apply(Inmueble::query());
        $query = (new InmuebleIndexQuery(
            $request->validated(),
            $request->user()->can('propietarios.ver')
        ))->apply($query);
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return InmuebleResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateInmuebleRequest $request, CreateInmuebleAction $action): JsonResponse
    {
        Gate::authorize('create', Inmueble::class);

        return (new InmuebleResource($action->execute($request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Inmueble $inmueble): InmuebleResource
    {
        Gate::authorize('view', $inmueble);

        $relations = ['categoria:id,nombre'];

        if (request()->user()?->can('propietarios.ver')) {
            $relations[] = 'propietario:id,nombre_razon_social';
        }

        return new InmuebleResource($inmueble->load($relations));
    }

    public function update(
        UpdateInmuebleRequest $request,
        Inmueble $inmueble,
        UpdateInmuebleAction $action
    ): InmuebleResource {
        Gate::authorize('update', $inmueble);

        return new InmuebleResource($action->execute($request->user(), $inmueble, $request->validated()));
    }

    public function destroy(Inmueble $inmueble, DeleteInmuebleAction $action): Response
    {
        Gate::authorize('delete', $inmueble);
        $action->execute($inmueble);

        return response()->noContent();
    }
}
