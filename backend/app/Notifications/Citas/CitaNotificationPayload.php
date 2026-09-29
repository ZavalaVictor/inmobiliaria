<?php

namespace App\Notifications\Citas;

use App\Models\Cita;
use App\Models\CitaHistorial;

final class CitaNotificationPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function confirmed(Cita $cita): array
    {
        $property = self::propertySuffix($cita);

        return self::base(
            'cita_confirmada',
            'Cita confirmada',
            'Se registró una cita para '.self::dateRange($cita->fecha_inicio, $cita->fecha_fin).$property.'.',
            $cita,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function rescheduled(Cita $cita, CitaHistorial $history): array
    {
        return self::base(
            'cita_reprogramada',
            'Cita reprogramada',
            'La cita cambió de '.self::dateRange($history->fecha_inicio_anterior, $history->fecha_fin_anterior)
                .' a '.self::dateRange($history->fecha_inicio_nueva, $history->fecha_fin_nueva).'.',
            $cita,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function cancelled(Cita $cita): array
    {
        return self::base(
            'cita_cancelada',
            'Cita cancelada',
            'La cita programada para '.self::dateRange($cita->fecha_inicio, $cita->fecha_fin).' fue cancelada.',
            $cita,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(string $type, string $title, string $message, Cita $cita): array
    {
        return [
            'tipo' => $type,
            'titulo' => $title,
            'mensaje' => $message,
            'url' => '/citas/'.$cita->getKey(),
            'entidad' => [
                'tipo' => 'cita',
                'id' => $cita->getKey(),
            ],
        ];
    }

    private static function dateRange(mixed $start, mixed $end): string
    {
        return $start->format('Y-m-d H:i').' - '.$end->format('H:i');
    }

    private static function propertySuffix(Cita $cita): string
    {
        $title = $cita->inmueble?->titulo;

        return is_string($title) && $title !== '' ? ' en '.$title : '';
    }
}
