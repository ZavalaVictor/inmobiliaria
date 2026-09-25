<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_agente', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('agente_id');
            $table->boolean('es_principal')->default(false);
            $table->dateTime('fecha_asignacion')->useCurrent();
            $table->timestamps();

            $table->unique(['cliente_id', 'agente_id'], 'uq_cliente_agente');
            $table->index('cliente_id', 'idx_cliente_agente_cliente');
            $table->index('agente_id', 'idx_cliente_agente_agente');
            $table->index(['cliente_id', 'es_principal'], 'idx_cliente_agente_principal');
            $table->foreign('agente_id', 'fk_cliente_agente_agente')
                ->references('id')
                ->on('agentes')
                ->cascadeOnDelete();
            $table->foreign('cliente_id', 'fk_cliente_agente_cliente')
                ->references('id')
                ->on('clientes')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE cliente_agente ADD CONSTRAINT chk_cliente_agente_principal CHECK (es_principal IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_agente');
    }
};
