<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Categorias\CreateCategoriaAction;
use App\Actions\Categorias\DeleteCategoriaAction;
use App\Actions\Categorias\UpdateCategoriaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\CreateCategoriaRequest;
use App\Http\Requests\Categoria\IndexCategoriaRequest;
use App\Http\Requests\Categoria\UpdateCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use App\Queries\Categorias\CategoriaIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CategoriaController extends Controller
{
    public function index(IndexCategoriaRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('categorias.ver');

        $query = (new CategoriaIndexQuery($request->validated()))->apply(Categoria::query());
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return CategoriaResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateCategoriaRequest $request, CreateCategoriaAction $action): JsonResponse
    {
        Gate::authorize('categorias.crear');

        return (new CategoriaResource($action->execute($request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Categoria $categoria): CategoriaResource
    {
        Gate::authorize('categorias.ver');

        return new CategoriaResource($categoria);
    }

    public function update(
        UpdateCategoriaRequest $request,
        Categoria $categoria,
        UpdateCategoriaAction $action
    ): CategoriaResource {
        Gate::authorize('categorias.actualizar');

        return new CategoriaResource($action->execute($categoria, $request->validated()));
    }

    public function destroy(Categoria $categoria, DeleteCategoriaAction $action): Response
    {
        Gate::authorize('categorias.eliminar');
        $action->execute($categoria);

        return response()->noContent();
    }
}
