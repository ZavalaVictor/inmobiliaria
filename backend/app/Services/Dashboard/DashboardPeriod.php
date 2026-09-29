<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class DashboardPeriod
{
    public function __construct(
        public string $clave,
        public CarbonImmutable $inicio,
        public CarbonImmutable $fin,
        public CarbonImmutable $ahora,
    ) {}

    public static function fromKey(string $key): self
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        return match ($key) {
            'mes' => new self($key, $now->startOfMonth(), $now->endOfMonth(), $now),
            'trimestre' => new self(
                $key,
                $now->startOfQuarter(),
                $now->endOfQuarter(),
                $now,
            ),
            'anio' => new self($key, $now->startOfYear(), $now->endOfYear(), $now),
            default => throw new InvalidArgumentException('Periodo de dashboard inválido.'),
        };
    }

    public function lastTwelveMonthsStart(): CarbonImmutable
    {
        return $this->ahora->startOfMonth()->subMonths(11);
    }

    /**
     * @return array{clave: string, inicio: string, fin: string}
     */
    public function toArray(): array
    {
        return [
            'clave' => $this->clave,
            'inicio' => $this->inicio->toISOString(),
            'fin' => $this->fin->toISOString(),
        ];
    }
}
