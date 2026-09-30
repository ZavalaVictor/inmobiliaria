<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clientes\DisableClientePortalAction;
use App\Actions\Clientes\EnableClientePortalAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortalCliente\NoPayloadRequest;
use App\Models\Cliente;
use App\Services\PortalCliente\PortalClienteAccess;
use Illuminate\Http\JsonResponse;

final class ClientePortalController extends Controller
{
    public function enable(
        NoPayloadRequest $request,
        Cliente $cliente,
        PortalClienteAccess $access,
        EnableClientePortalAction $action,
    ): JsonResponse {
        $access->authorizeAdministrator($request->user());
        $data = $action->execute($request->user(), $cliente);
        $status = ($data['portal']['ya_habilitado'] ?? false) || ($data['portal']['rehabilitado'] ?? false) ? 200 : 201;

        return response()->json(['data' => $data], $status);
    }

    public function disable(
        NoPayloadRequest $request,
        Cliente $cliente,
        PortalClienteAccess $access,
        DisableClientePortalAction $action,
    ): JsonResponse {
        $access->authorizeAdministrator($request->user());

        return response()->json(['data' => $action->execute($request->user(), $cliente)]);
    }
}
