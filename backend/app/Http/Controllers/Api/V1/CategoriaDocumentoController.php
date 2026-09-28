<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CategoriasDocumentos\CreateCategoriaDocumentoAction;
use App\Actions\CategoriasDocumentos\DeleteCategoriaDocumentoAction;
use App\Actions\CategoriasDocumentos\UpdateCategoriaDocumentoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaDocumento\CreateCategoriaDocumentoRequest;
use App\Http\Requests\CategoriaDocumento\IndexCategoriaDocumentoRequest;
use App\Http\Requests\CategoriaDocumento\UpdateCategoriaDocumentoRequest;
use App\Http\Resources\CategoriaDocumentoResource;
use App\Models\CategoriaDocumento;
use App\Queries\CategoriasDocumentos\CategoriaDocumentoIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoriaDocumentoController extends Controller
{
    public function index(IndexCategoriaDocumentoRequest $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('categorias_documentos.ver'), 403);

        $filters = $request->validated();
        $query = (new CategoriaDocumentoIndexQuery($filters))->apply(CategoriaDocumento::query());
        $perPage = (int) ($filters['per_page'] ?? 15);

        return CategoriaDocumentoResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateCategoriaDocumentoRequest $request, CreateCategoriaDocumentoAction $action): JsonResponse
    {
        abort_unless($request->user()->can('categorias_documentos.crear'), 403);

        return (new CategoriaDocumentoResource($action->execute($request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, CategoriaDocumento $categoria): CategoriaDocumentoResource
    {
        abort_unless($request->user()->can('categorias_documentos.ver'), 403);

        return new CategoriaDocumentoResource($categoria);
    }

    public function update(
        UpdateCategoriaDocumentoRequest $request,
        CategoriaDocumento $categoria,
        UpdateCategoriaDocumentoAction $action,
    ): CategoriaDocumentoResource {
        abort_unless($request->user()->can('categorias_documentos.actualizar'), 403);

        return new CategoriaDocumentoResource($action->execute($categoria, $request->validated()));
    }

    public function destroy(Request $request, CategoriaDocumento $categoria, DeleteCategoriaDocumentoAction $action): Response
    {
        abort_unless($request->user()->can('categorias_documentos.eliminar'), 403);
        $action->execute($categoria);

        return response()->noContent();
    }
}
