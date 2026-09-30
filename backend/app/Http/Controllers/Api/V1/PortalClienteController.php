<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\IndexCitasRequest;
use App\Http\Requests\PortalCliente\IndexInmueblesInteresRequest;
use App\Http\Resources\PortalCitaResource;
use App\Http\Resources\PortalClienteResource;
use App\Http\Resources\PortalInmuebleInteresResource;
use App\Models\User;
use App\Services\PortalCliente\PortalClienteAccess;
use App\Services\PortalCliente\PortalClienteQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PortalClienteController extends Controller
{
    public function summary(
        PortalClienteAccess $access,
        PortalClienteQuery $query,
    ): JsonResponse {
        /** @var User $user */
        $user = request()->user();
        $cliente = $access->authorize($user);
        $data = $query->summary($user, $cliente);

        return response()->json([
            'data' => [
                'cliente' => (new PortalClienteResource($data['cliente']))->resolve(request()),
                'inmuebles_interes' => [
                    'total' => $data['inmuebles_interes']['total'],
                    'items' => PortalInmuebleInteresResource::collection($data['inmuebles_interes']['items'])->resolve(request()),
                ],
                'citas' => [
                    'proximas' => PortalCitaResource::collection($data['citas']['proximas'])->resolve(request()),
                    'historial_reciente' => PortalCitaResource::collection($data['citas']['historial_reciente'])->resolve(request()),
                ],
                'notificaciones' => $data['notificaciones'],
            ],
        ]);
    }

    public function interests(
        IndexInmueblesInteresRequest $request,
        PortalClienteAccess $access,
        PortalClienteQuery $query,
    ): AnonymousResourceCollection {
        $cliente = $access->authorize($request->user());

        return PortalInmuebleInteresResource::collection(
            $query->interests($cliente, (int) ($request->validated('per_page') ?? 15))
        );
    }

    public function appointments(
        IndexCitasRequest $request,
        PortalClienteAccess $access,
        PortalClienteQuery $query,
    ): AnonymousResourceCollection {
        $cliente = $access->authorize($request->user());
        $type = $request->validated('tipo') ?? 'proximas';

        return PortalCitaResource::collection(
            $query->appointments($cliente, $type, (int) ($request->validated('per_page') ?? 15))
        );
    }
}
