<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cita\IndexCitaHistorialRequest;
use App\Http\Resources\CitaHistorialResource;
use App\Models\Cita;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CitaHistorialController extends Controller
{
    public function index(IndexCitaHistorialRequest $request, Cita $cita): AnonymousResourceCollection
    {
        Gate::forUser($request->user())->authorize('view', $cita);

        $filters = $request->validated();
        $historial = $cita->historial()
            ->with([
                'modificadoPor:id,nombres,apellido_paterno,apellido_materno',
                'agenteAnterior:id,numero_empleado,user_id',
                'agenteAnterior.user:id,nombres,apellido_paterno,apellido_materno',
                'agenteNuevo:id,numero_empleado,user_id',
                'agenteNuevo.user:id,nombres,apellido_paterno,apellido_materno',
            ])
            ->orderByDesc('fecha_modificacion')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();

        return CitaHistorialResource::collection($historial);
    }
}
