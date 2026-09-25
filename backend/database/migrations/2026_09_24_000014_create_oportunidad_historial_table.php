<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidad_historial', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('oportunidad_id');
            $table->unsignedBigInteger('cambiado_por_user_id');
            $table->string('tipo_evento', 30)->default('cambio_etapa');
            $table->string('etapa_anterior', 30)->nullable();
            $table->string('etapa_nueva', 30)->nullable();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20)->nullable();
            $table->string('comentario', 500)->nullable();
            $table->dateTime('fecha_cambio')->useCurrent();
            $table->timestamp('created_at')->nullable();

            $table->index('oportunidad_id', 'idx_historial_oportunidad');
            $table->index('cambiado_por_user_id', 'idx_historial_usuario');
            $table->index('tipo_evento', 'idx_historial_tipo_evento');
            $table->index('fecha_cambio', 'idx_historial_fecha');
            $table->foreign('oportunidad_id', 'fk_historial_oportunidad')->references('id')->on('oportunidades');
            $table->foreign('cambiado_por_user_id', 'fk_historial_oportunidad_usuario')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE oportunidad_historial ADD CONSTRAINT chk_historial_tipo_evento CHECK (tipo_evento IN ('creacion', 'cambio_etapa', 'cambio_estado'))");
        DB::statement("ALTER TABLE oportunidad_historial ADD CONSTRAINT chk_historial_etapa_anterior CHECK (etapa_anterior IS NULL OR etapa_anterior IN ('contacto_inicial', 'cita', 'negociacion', 'documentacion', 'cierre'))");
        DB::statement("ALTER TABLE oportunidad_historial ADD CONSTRAINT chk_historial_etapa_nueva CHECK (etapa_nueva IS NULL OR etapa_nueva IN ('contacto_inicial', 'cita', 'negociacion', 'documentacion', 'cierre'))");
        DB::statement("ALTER TABLE oportunidad_historial ADD CONSTRAINT chk_historial_estado_anterior CHECK (estado_anterior IS NULL OR estado_anterior IN ('activa', 'ganada', 'perdida', 'cancelada'))");
        DB::statement("ALTER TABLE oportunidad_historial ADD CONSTRAINT chk_historial_estado_nuevo CHECK (estado_nuevo IS NULL OR estado_nuevo IN ('activa', 'ganada', 'perdida', 'cancelada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidad_historial');
    }
};
