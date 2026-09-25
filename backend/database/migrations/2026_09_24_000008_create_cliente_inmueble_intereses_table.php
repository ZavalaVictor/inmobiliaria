<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_inmueble_intereses', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('inmueble_id');
            $table->string('nivel_interes', 20)->nullable();
            $table->string('estado', 20)->default('activo');
            $table->text('notas')->nullable();
            $table->dateTime('fecha_interes')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cliente_id', 'inmueble_id'], 'uq_cliente_inmueble');
            $table->index('cliente_id', 'idx_intereses_cliente');
            $table->index('inmueble_id', 'idx_intereses_inmueble');
            $table->index('estado', 'idx_intereses_estado');
            $table->index('fecha_interes', 'idx_intereses_fecha');
            $table->index('deleted_at', 'idx_intereses_deleted_at');
            $table->foreign('cliente_id', 'fk_intereses_cliente')
                ->references('id')
                ->on('clientes')
                ->cascadeOnDelete();
            $table->foreign('inmueble_id', 'fk_intereses_inmueble')
                ->references('id')
                ->on('inmuebles')
                ->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE cliente_inmueble_intereses ADD CONSTRAINT chk_intereses_nivel CHECK (nivel_interes IS NULL OR nivel_interes IN ('bajo', 'medio', 'alto'))");
        DB::statement("ALTER TABLE cliente_inmueble_intereses ADD CONSTRAINT chk_intereses_estado CHECK (estado IN ('activo', 'descartado', 'convertido'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_inmueble_intereses');
    }
};
