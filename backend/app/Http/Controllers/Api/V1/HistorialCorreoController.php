<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistorialCorreo\IndexHistorialCorreoRequest;
use App\Http\Resources\HistorialCorreoResource;
use App\Models\HistorialCorreo;
use App\Queries\HistorialCorreos\HistorialCorreoIndexQuery;
use App\Queries\Visibility\VisibleHistorialCorreosQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class HistorialCorreoController extends Controller
{
    public function index(IndexHistorialCorreoRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', HistorialCorreo::class);

        $filters = $request->validated();
        $query = (new VisibleHistorialCorreosQuery($request->user()))
            ->apply(HistorialCorreo::query());

        $correos = (new HistorialCorreoIndexQuery($filters))
            ->apply($query)
            ->with($this->safeRelations())
            ->paginate((int) ($filters['per_page'] ?? 15));

        return HistorialCorreoResource::collection($correos);
    }

    public function show(HistorialCorreo $correo): HistorialCorreoResource
    {
        Gate::authorize('view', $correo);

        return new HistorialCorreoResource($correo->load($this->safeRelations()));
    }

    /**
     * @return array<int, string>
     */
    private function safeRelations(): array
    {
        return [
            'destinatario:id,nombres,apellido_paterno,apellido_materno',
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'cita:id,fecha_inicio,fecha_fin,estado',
            'enviadoPor:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
