<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmueble\IndexPublicInmuebleRequest;
use App\Http\Resources\PublicInmuebleResource;
use App\Models\Inmueble;
use App\Queries\Inmuebles\PublicInmuebleIndexQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PublicInmuebleController extends Controller
{
    public function index(IndexPublicInmuebleRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 12);

        $properties = (new PublicInmuebleIndexQuery)
            ->apply(Inmueble::query(), $filters)
            ->paginate($perPage)
            ->withQueryString();

        return PublicInmuebleResource::collection($properties);
    }

    public function show(Inmueble $inmueble): PublicInmuebleResource
    {
        abort_unless($inmueble->publicado, 404);

        return new PublicInmuebleResource($inmueble->load([
            'categoria:id,nombre',
            'imagenPrincipal:id,inmueble_id,url_publica,nombre_original,mime_type,es_principal,orden,texto_alternativo',
            'imagenes:id,inmueble_id,url_publica,nombre_original,mime_type,es_principal,orden,texto_alternativo',
        ]));
    }
}
