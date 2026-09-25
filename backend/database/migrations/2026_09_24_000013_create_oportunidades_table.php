<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('inmueble_id')->nullable();
            $table->unsignedBigInteger('agente_principal_id')->nullable();
            $table->unsignedBigInteger('solicitud_informacion_id')->nullable();
            $table->string('titulo', 150);
            $table->string('etapa', 30)->default('contacto_inicial');
            $table->string('estado', 20)->default('activa');
            $table->text('notas')->nullable();
            $table->dateTime('fecha_apertura')->useCurrent();
            $table->dateTime('fecha_cierre')->nullable();
            $table->string('motivo_perdida', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('solicitud_informacion_id', 'uq_oportunidades_solicitud');
            $table->index('cliente_id', 'idx_oportunidades_cliente');
            $table->index('inmueble_id', 'idx_oportunidades_inmueble');
            $table->index('agente_principal_id', 'idx_oportunidades_agente');
            $table->index('etapa', 'idx_oportunidades_etapa');
            $table->index('estado', 'idx_oportunidades_estado');
            $table->index('fecha_apertura', 'idx_oportunidades_fecha_apertura');
            $table->index('deleted_at', 'idx_oportunidades_deleted_at');
            $table->foreign('agente_principal_id', 'fk_oportunidades_agente')
                ->references('id')
                ->on('agentes')
                ->nullOnDelete();
            $table->foreign('cliente_id', 'fk_oportunidades_cliente')->references('id')->on('clientes');
            $table->foreign('inmueble_id', 'fk_oportunidades_inmueble')
                ->references('id')
                ->on('inmuebles')
                ->nullOnDelete();
            $table->foreign('solicitud_informacion_id', 'fk_oportunidades_solicitud')
                ->references('id')
                ->on('solicitudes_informacion')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE oportunidades ADD CONSTRAINT chk_oportunidades_etapa CHECK (etapa IN ('contacto_inicial', 'cita', 'negociacion', 'documentacion', 'cierre'))");
        DB::statement("ALTER TABLE oportunidades ADD CONSTRAINT chk_oportunidades_estado CHECK (estado IN ('activa', 'ganada', 'perdida', 'cancelada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
    }
};
