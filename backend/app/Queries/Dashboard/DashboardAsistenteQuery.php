<?php

namespace App\Queries\Dashboard;

use App\Services\Dashboard\DashboardPeriod;

final class DashboardAsistenteQuery
{
    public function __construct(private readonly DashboardGlobalQuery $global) {}

    /**
     * @return array<string, mixed>
     */
    public function build(DashboardPeriod $period): array
    {
        $summary = $this->global->assistantSummary($period);
        $lists = $this->global->operationalLists($period);

        return [
            'resumen' => $summary,
            'graficas' => [],
            'listas' => [
                'solicitudes_recientes' => $lists['solicitudes_recientes'],
                'citas_proximas' => $lists['citas_proximas'],
            ],
        ];
    }
}
