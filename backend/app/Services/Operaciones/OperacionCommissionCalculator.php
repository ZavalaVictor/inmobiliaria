<?php

namespace App\Services\Operaciones;

use InvalidArgumentException;

final class OperacionCommissionCalculator
{
    public function calculate(string $monto, string $porcentaje): string
    {
        $montoCents = $this->toScaledInteger($monto, 2);
        $percentageBasisPoints = $this->toScaledInteger($porcentaje, 2);
        $commissionCents = intdiv(($montoCents * $percentageBasisPoints) + 5000, 10000);

        return intdiv($commissionCents, 100).'.'.str_pad((string) ($commissionCents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function toScaledInteger(string $value, int $scale): int
    {
        $value = trim($value);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('El valor decimal no tiene un formato válido.');
        }

        [$integer, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $decimal = str_pad($decimal, $scale, '0');

        return ((int) $integer * (10 ** $scale)) + (int) substr($decimal, 0, $scale);
    }
}
