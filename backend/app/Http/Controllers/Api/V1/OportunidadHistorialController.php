<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Oportunidad\IndexOportunidadHistorialRequest;
use App\Http\Resources\OportunidadHistorialResource;
use App\Models\Oportunidad;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OportunidadHistorialController extends Controller
{
    public function index(
        IndexOportunidadHistorialRequest $request,
        Oportunidad $oportunidad,
    ): AnonymousResourceCollection {
        Gate::forUser(request()->user())->authorize('view', $oportunidad);

        $historial = $oportunidad->historial()
            ->with('cambiadoPor:id,nombres,apellido_paterno,apellido_materno')
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id')
            ->paginate((int) ($request->validated()['per_page'] ?? 25))
            ->withQueryString();

        return OportunidadHistorialResource::collection($historial);
    }
}
