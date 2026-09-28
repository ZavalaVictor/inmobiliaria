<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Solicitudes\CreatePublicSolicitudInformacionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudInformacion\PublicCreateSolicitudInformacionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PublicSolicitudInformacionController extends Controller
{
    public function store(
        PublicCreateSolicitudInformacionRequest $request,
        CreatePublicSolicitudInformacionAction $action,
    ): JsonResponse {
        $action->execute($request->validated());

        return response()->json([
            'message' => 'Solicitud recibida correctamente.',
        ], Response::HTTP_CREATED);
    }
}
