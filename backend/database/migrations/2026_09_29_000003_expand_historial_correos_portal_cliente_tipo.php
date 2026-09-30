<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ORIGINAL = "'confirmacion_cita', 'reprogramacion_cita', 'cancelacion_cita', 'recuperacion_password', 'respaldo', 'restauracion', 'seguridad', 'otro', 'solicitud_recibida', 'solicitud_asignada', 'oportunidad_cambio_etapa', 'oportunidad_cierre', 'operacion_creada', 'operacion_cierre', 'documento_cargado'";

    private const EXPANDED = "'confirmacion_cita', 'reprogramacion_cita', 'cancelacion_cita', 'recuperacion_password', 'respaldo', 'restauracion', 'seguridad', 'otro', 'solicitud_recibida', 'solicitud_asignada', 'oportunidad_cambio_etapa', 'oportunidad_cierre', 'operacion_creada', 'operacion_cierre', 'documento_cargado', 'portal_cliente_activacion'";

    public function up(): void
    {
        DB::statement('ALTER TABLE historial_correos DROP CONSTRAINT chk_correos_tipo');
        DB::statement('ALTER TABLE historial_correos ADD CONSTRAINT chk_correos_tipo CHECK (tipo IN ('.self::EXPANDED.'))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE historial_correos DROP CONSTRAINT chk_correos_tipo');
        DB::statement('ALTER TABLE historial_correos ADD CONSTRAINT chk_correos_tipo CHECK (tipo IN ('.self::ORIGINAL.'))');
    }
};
