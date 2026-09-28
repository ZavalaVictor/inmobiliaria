<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Citas\CreateCitaAction;
use App\Actions\Citas\DeleteCitaAction;
use App\Actions\Citas\ReprogramarCitaAction;
use App\Actions\Citas\UpdateCitaAction;
use App\Actions\Citas\UpdateEstadoCitaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cita\CreateCitaRequest;
use App\Http\Requests\Cita\IndexCitaRequest;
use App\Http\Requests\Cita\ReprogramarCitaRequest;
use App\Http\Requests\Cita\UpdateCitaRequest;
use App\Http\Requests\Cita\UpdateEstadoCitaRequest;
use App\Http\Resources\CitaResource;
use App\Models\Cita;
use App\Queries\Citas\CitaIndexQuery;
use App\Queries\Visibility\VisibleCitasQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CitaController extends Controller
{
    public function index(IndexCitaRequest $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user())->authorize('viewAny', Cita::class);

        $filters = $request->validated();
        $query = (new VisibleCitasQuery($request->user()))->apply(Cita::query());
        $query = (new CitaIndexQuery($filters))->apply($query);

        return CitaResource::collection(
            $query->with($this->relations())
                ->paginate((int) ($filters['per_page'] ?? 15))
                ->withQueryString()
        );
    }

    public function store(CreateCitaRequest $request, CreateCitaAction $action): JsonResponse
    {
        Gate::forUser($request->user())->authorize('create', Cita::class);

        return (new CitaResource($action->execute($request->user(), $request->validated())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Cita $cita): CitaResource
    {
        Gate::forUser(request()->user())->authorize('view', $cita);

        return new CitaResource($cita->load($this->relations()));
    }

    public function update(UpdateCitaRequest $request, Cita $cita, UpdateCitaAction $action): CitaResource
    {
        Gate::forUser($request->user())->authorize('update', $cita);

        return new CitaResource($action->execute($request->user(), $cita, $request->validated()));
    }

    public function reprogramar(
        ReprogramarCitaRequest $request,
        Cita $cita,
        ReprogramarCitaAction $action,
    ): CitaResource {
        Gate::forUser($request->user())->authorize('update', $cita);

        return new CitaResource($action->execute($request->user(), $cita, $request->validated()));
    }

    public function estado(
        UpdateEstadoCitaRequest $request,
        Cita $cita,
        UpdateEstadoCitaAction $action,
    ): CitaResource {
        Gate::forUser($request->user())->authorize('update', $cita);

        return new CitaResource($action->execute($request->user(), $cita, $request->validated()));
    }

    public function destroy(Cita $cita, DeleteCitaAction $action): Response
    {
        Gate::forUser(request()->user())->authorize('delete', $cita);
        $action->execute($cita);

        return response()->noContent();
    }

    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'agente:id,numero_empleado,user_id',
            'agente.user:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'oportunidad:id,titulo,etapa,estado',
            'creadoPor:id,nombres,apellido_paterno,apellido_materno',
        ];
    }
}
