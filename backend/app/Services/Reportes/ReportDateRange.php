<?php

namespace App\Services\Reportes;

use Carbon\CarbonImmutable;

final readonly class ReportDateRange
{
    public function __construct(
        public string $desde,
        public string $hasta,
        public CarbonImmutable $inicio,
        public CarbonImmutable $fin,
    ) {}

    public static function fromStrings(string $desde, string $hasta): self
    {
        $timezone = config('app.timezone');
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $desde, $timezone)->startOfDay();
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $hasta, $timezone)->endOfDay();

        return new self($desde, $hasta, $start, $end);
    }

    /** @return array{desde: string, hasta: string} */
    public function toArray(): array
    {
        return ['desde' => $this->desde, 'hasta' => $this->hasta];
    }
}
