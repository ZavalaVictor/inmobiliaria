<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Oportunidades\ChangeOportunidadEstadoAction;
use App\Actions\Oportunidades\ChangeOportunidadEtapaAction;
use App\Actions\Oportunidades\CreateOportunidadAction;
use App\Actions\Oportunidades\DeleteOportunidadAction;
use App\Actions\Oportunidades\UpdateOportunidadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Oportunidad\ChangeOportunidadEstadoRequest;
use App\Http\Requests\Oportunidad\ChangeOportunidadEtapaRequest;
use App\Http\Requests\Oportunidad\CreateOportunidadRequest;
use App\Http\Requests\Oportunidad\IndexOportunidadRequest;
use App\Http\Requests\Oportunidad\UpdateOportunidadRequest;
use App\Http\Resources\OportunidadResource;
use App\Models\Oportunidad;
use App\Queries\Oportunidades\OportunidadIndexQuery;
use App\Queries\Visibility\VisibleOportunidadesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OportunidadController extends Controller
{
    public function index(IndexOportunidadRequest $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user())->authorize('viewAny', Oportunidad::class);

        $filters = $request->validated();
        $query = (new VisibleOportunidadesQuery($request->user()))
            ->apply(Oportunidad::query());
        $query = (new OportunidadIndexQuery($filters))->apply($query);

        $oportunidades = $query
            ->with($this->relations())
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return OportunidadResource::collection($oportunidades);
    }

    public function store(
        CreateOportunidadRequest $request,
        CreateOportunidadAction $action,
    ): JsonResponse {
        Gate::forUser($request->user())->authorize('create', Oportunidad::class);

        return (new OportunidadResource(
            $action->execute($request->user(), $request->validated())
        ))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Oportunidad $oportunidad): OportunidadResource
    {
        Gate::forUser(request()->user())->authorize('view', $oportunidad);

        return new OportunidadResource($oportunidad->load($this->relations()));
    }

    public function update(
        UpdateOportunidadRequest $request,
        Oportunidad $oportunidad,
        UpdateOportunidadAction $action,
    ): OportunidadResource {
        Gate::forUser($request->user())->authorize('update', $oportunidad);

        return new OportunidadResource(
            $action->execute($request->user(), $oportunidad, $request->validated())
        );
    }

    public function etapa(
        ChangeOportunidadEtapaRequest $request,
        Oportunidad $oportunidad,
        ChangeOportunidadEtapaAction $action,
    ): OportunidadResource {
        Gate::forUser($request->user())->authorize('update', $oportunidad);

        return new OportunidadResource(
            $action->execute($request->user(), $oportunidad, $request->validated())
        );
    }

    public function estado(
        ChangeOportunidadEstadoRequest $request,
        Oportunidad $oportunidad,
        ChangeOportunidadEstadoAction $action,
    ): OportunidadResource {
        Gate::forUser($request->user())->authorize('update', $oportunidad);

        return new OportunidadResource(
            $action->execute($request->user(), $oportunidad, $request->validated())
        );
    }

    public function destroy(
        Oportunidad $oportunidad,
        DeleteOportunidadAction $action,
    ): Response {
        Gate::forUser(request()->user())->authorize('delete', $oportunidad);
        $action->execute($oportunidad);

        return response()->noContent();
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'agentePrincipal:id,numero_empleado,user_id',
            'agentePrincipal.user:id,nombres,apellido_paterno,apellido_materno',
            'solicitudInformacion:id,nombre,estado,medio_preferido',
        ];
    }
}
