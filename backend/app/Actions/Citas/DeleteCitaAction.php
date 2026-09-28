<?php

namespace App\Actions\Citas;

use App\Models\Cita;
use App\Services\Citas\CitaAvailabilityService;
use Illuminate\Support\Facades\DB;

final class DeleteCitaAction
{
    public function __construct(private readonly CitaAvailabilityService $availability) {}

    public function execute(Cita $cita): void
    {
        DB::transaction(function () use ($cita): void {
            $this->availability->lockResources([$cita->agente_id], $cita->inmueble_id);
            Cita::withTrashed()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail()->delete();
        });
    }
}
