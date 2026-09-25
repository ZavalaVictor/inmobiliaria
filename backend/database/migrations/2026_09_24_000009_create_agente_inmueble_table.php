<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agente_inmueble', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('agente_id');
            $table->unsignedBigInteger('inmueble_id');
            $table->boolean('es_principal')->default(false);
            $table->dateTime('fecha_asignacion')->useCurrent();
            $table->timestamps();

            $table->unique(['agente_id', 'inmueble_id'], 'uq_agente_inmueble');
            $table->index('agente_id', 'idx_agente_inmueble_agente');
            $table->index('inmueble_id', 'idx_agente_inmueble_inmueble');
            $table->index(['inmueble_id', 'es_principal'], 'idx_agente_inmueble_principal');
            $table->foreign('agente_id', 'fk_agente_inmueble_agente')
                ->references('id')
                ->on('agentes')
                ->cascadeOnDelete();
            $table->foreign('inmueble_id', 'fk_agente_inmueble_inmueble')
                ->references('id')
                ->on('inmuebles')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE agente_inmueble ADD CONSTRAINT chk_agente_inmueble_principal CHECK (es_principal IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('agente_inmueble');
    }
};
