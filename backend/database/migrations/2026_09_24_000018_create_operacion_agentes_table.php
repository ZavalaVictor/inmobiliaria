<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operacion_agentes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('operacion_id');
            $table->unsignedBigInteger('agente_id');
            $table->boolean('es_principal')->default(false);
            $table->decimal('porcentaje_comision', 5, 2)->default(0.00);
            $table->decimal('monto_comision', 14, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['operacion_id', 'agente_id'], 'uq_operacion_agente');
            $table->index('operacion_id', 'idx_operacion_agentes_operacion');
            $table->index('agente_id', 'idx_operacion_agentes_agente');
            $table->index(['operacion_id', 'es_principal'], 'idx_operacion_agentes_principal');
            $table->foreign('agente_id', 'fk_operacion_agentes_agente')->references('id')->on('agentes');
            $table->foreign('operacion_id', 'fk_operacion_agentes_operacion')->references('id')->on('operaciones');
        });

        DB::statement('ALTER TABLE operacion_agentes ADD CONSTRAINT chk_operacion_agentes_principal CHECK (es_principal IN (0, 1))');
        DB::statement('ALTER TABLE operacion_agentes ADD CONSTRAINT chk_operacion_agentes_porcentaje CHECK (porcentaje_comision >= 0 AND porcentaje_comision <= 100)');
        DB::statement('ALTER TABLE operacion_agentes ADD CONSTRAINT chk_operacion_agentes_monto CHECK (monto_comision >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('operacion_agentes');
    }
};
