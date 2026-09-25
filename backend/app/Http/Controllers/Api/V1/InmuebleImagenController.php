<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\InmuebleImagenes\DeleteInmuebleImagenAction;
use App\Actions\InmuebleImagenes\ReorderInmuebleImagenesAction;
use App\Actions\InmuebleImagenes\SetPrincipalInmuebleImagenAction;
use App\Actions\InmuebleImagenes\UpdateInmuebleImagenAction;
use App\Actions\InmuebleImagenes\UploadInmuebleImagenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\InmuebleImagen\ReorderInmuebleImagenesRequest;
use App\Http\Requests\InmuebleImagen\UpdateInmuebleImagenRequest;
use App\Http\Requests\InmuebleImagen\UploadInmuebleImagenRequest;
use App\Http\Resources\InmuebleImagenResource;
use App\Models\Inmueble;
use App\Models\InmuebleImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InmuebleImagenController extends Controller
{
    public function index(Request $request, Inmueble $inmueble)
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('viewAny', InmuebleImagen::class);

        $imagenes = $inmueble->imagenes()
            ->orderByDesc('es_principal')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return InmuebleImagenResource::collection($imagenes);
    }

    public function store(UploadInmuebleImagenRequest $request, Inmueble $inmueble, UploadInmuebleImagenAction $action)
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('create', InmuebleImagen::class);

        $validated = $request->validated();
        $imagen = $action->execute(
            $inmueble,
            $validated['imagen'],
            $validated['texto_alternativo'] ?? null,
        );

        return (new InmuebleImagenResource($imagen))->response()->setStatusCode(201);
    }

    public function update(UpdateInmuebleImagenRequest $request, Inmueble $inmueble, InmuebleImagen $imagen, UpdateInmuebleImagenAction $action)
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('update', $imagen);

        return new InmuebleImagenResource($action->execute($imagen, $request->validated()));
    }

    public function principal(Inmueble $inmueble, InmuebleImagen $imagen, SetPrincipalInmuebleImagenAction $action)
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('update', $imagen);

        return new InmuebleImagenResource($action->execute($inmueble, $imagen));
    }

    public function reorder(ReorderInmuebleImagenesRequest $request, Inmueble $inmueble, ReorderInmuebleImagenesAction $action)
    {
        Gate::authorize('view', $inmueble);
        abort_unless($request->user()->can('imagenes_inmuebles.actualizar'), 403);

        $imagenes = $action->execute($inmueble, $request->validated('imagenes'));

        return InmuebleImagenResource::collection($imagenes);
    }

    public function destroy(Inmueble $inmueble, InmuebleImagen $imagen, DeleteInmuebleImagenAction $action)
    {
        Gate::authorize('view', $inmueble);
        Gate::authorize('delete', $imagen);
        $action->execute($inmueble, $imagen);

        return response()->noContent();
    }
}
