<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Visualizaciones\RecordVisualizacionInmuebleAction;
use App\Enums\OrigenVisualizacionInmueble;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisualizacionInmueble\RecordVisualizacionInmuebleRequest;
use App\Models\Inmueble;
use Illuminate\Http\Response;

class PublicVisualizacionInmuebleController extends Controller
{
    public function store(
        RecordVisualizacionInmuebleRequest $request,
        Inmueble $inmueble,
        RecordVisualizacionInmuebleAction $action,
    ): Response {
        abort_unless($inmueble->publicado, Response::HTTP_NOT_FOUND);

        $action->execute($inmueble, $request, OrigenVisualizacionInmueble::LandingPublica);

        return response()->noContent();
    }
}
