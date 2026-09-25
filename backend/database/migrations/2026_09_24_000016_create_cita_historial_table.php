<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cita_historial', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cita_id');
            $table->unsignedBigInteger('modificado_por_user_id');
            $table->unsignedBigInteger('agente_anterior_id');
            $table->unsignedBigInteger('agente_nuevo_id');
            $table->dateTime('fecha_inicio_anterior');
            $table->dateTime('fecha_fin_anterior');
            $table->dateTime('fecha_inicio_nueva');
            $table->dateTime('fecha_fin_nueva');
            $table->string('motivo', 500);
            $table->string('tipo_cambio', 30)->default('reprogramacion');
            $table->dateTime('fecha_modificacion')->useCurrent();
            $table->timestamp('created_at')->nullable();

            $table->index('cita_id', 'idx_cita_historial_cita');
            $table->index('modificado_por_user_id', 'idx_cita_historial_usuario');
            $table->index('agente_anterior_id', 'idx_cita_historial_agente_anterior');
            $table->index('agente_nuevo_id', 'idx_cita_historial_agente_nuevo');
            $table->index('fecha_modificacion', 'idx_cita_historial_fecha');
            $table->foreign('agente_anterior_id', 'fk_cita_historial_agente_anterior')->references('id')->on('agentes');
            $table->foreign('agente_nuevo_id', 'fk_cita_historial_agente_nuevo')->references('id')->on('agentes');
            $table->foreign('cita_id', 'fk_cita_historial_cita')->references('id')->on('citas');
            $table->foreign('modificado_por_user_id', 'fk_cita_historial_usuario')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE cita_historial ADD CONSTRAINT chk_cita_historial_tipo CHECK (tipo_cambio IN ('reprogramacion', 'cambio_agente', 'reprogramacion_y_agente'))");
        DB::statement('ALTER TABLE cita_historial ADD CONSTRAINT chk_cita_historial_fecha_anterior CHECK (fecha_fin_anterior > fecha_inicio_anterior)');
        DB::statement('ALTER TABLE cita_historial ADD CONSTRAINT chk_cita_historial_fecha_nueva CHECK (fecha_fin_nueva > fecha_inicio_nueva)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_historial');
    }
};
