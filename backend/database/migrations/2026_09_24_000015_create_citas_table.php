<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('agente_id');
            $table->unsignedBigInteger('inmueble_id');
            $table->unsignedBigInteger('oportunidad_id')->nullable();
            $table->unsignedBigInteger('creado_por_user_id');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('estado', 20)->default('programada');
            $table->string('motivo', 255)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id', 'idx_citas_cliente');
            $table->index('agente_id', 'idx_citas_agente');
            $table->index('inmueble_id', 'idx_citas_inmueble');
            $table->index('oportunidad_id', 'idx_citas_oportunidad');
            $table->index('creado_por_user_id', 'idx_citas_creador');
            $table->index(['agente_id', 'fecha_inicio', 'fecha_fin'], 'idx_citas_agente_horario');
            $table->index(['inmueble_id', 'fecha_inicio', 'fecha_fin'], 'idx_citas_inmueble_horario');
            $table->index('estado', 'idx_citas_estado');
            $table->index('fecha_inicio', 'idx_citas_fecha_inicio');
            $table->index('deleted_at', 'idx_citas_deleted_at');
            $table->foreign('agente_id', 'fk_citas_agente')->references('id')->on('agentes');
            $table->foreign('cliente_id', 'fk_citas_cliente')->references('id')->on('clientes');
            $table->foreign('creado_por_user_id', 'fk_citas_creador')->references('id')->on('users');
            $table->foreign('inmueble_id', 'fk_citas_inmueble')->references('id')->on('inmuebles');
            $table->foreign('oportunidad_id', 'fk_citas_oportunidad')
                ->references('id')
                ->on('oportunidades')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE citas ADD CONSTRAINT chk_citas_fechas CHECK (fecha_fin > fecha_inicio)');
        DB::statement("ALTER TABLE citas ADD CONSTRAINT chk_citas_estado CHECK (estado IN ('programada', 'confirmada', 'completada', 'cancelada', 'no_asistio'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
