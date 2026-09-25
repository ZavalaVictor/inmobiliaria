<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operaciones', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('oportunidad_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('inmueble_id');
            $table->unsignedBigInteger('registrado_por_user_id');
            $table->string('tipo_operacion', 20);
            $table->decimal('monto', 14, 2);
            $table->dateTime('fecha_operacion')->useCurrent();
            $table->date('fecha_inicio_contrato')->nullable();
            $table->date('fecha_fin_contrato')->nullable();
            $table->string('estado', 20)->default('registrada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique('oportunidad_id', 'uq_operaciones_oportunidad');
            $table->index('cliente_id', 'idx_operaciones_cliente');
            $table->index('inmueble_id', 'idx_operaciones_inmueble');
            $table->index('registrado_por_user_id', 'idx_operaciones_usuario');
            $table->index('tipo_operacion', 'idx_operaciones_tipo');
            $table->index('fecha_operacion', 'idx_operaciones_fecha');
            $table->index('estado', 'idx_operaciones_estado');
            $table->foreign('cliente_id', 'fk_operaciones_cliente')->references('id')->on('clientes');
            $table->foreign('inmueble_id', 'fk_operaciones_inmueble')->references('id')->on('inmuebles');
            $table->foreign('oportunidad_id', 'fk_operaciones_oportunidad')->references('id')->on('oportunidades');
            $table->foreign('registrado_por_user_id', 'fk_operaciones_usuario')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE operaciones ADD CONSTRAINT chk_operaciones_tipo CHECK (tipo_operacion IN ('venta', 'renta'))");
        DB::statement('ALTER TABLE operaciones ADD CONSTRAINT chk_operaciones_monto CHECK (monto > 0)');
        DB::statement("ALTER TABLE operaciones ADD CONSTRAINT chk_operaciones_estado CHECK (estado IN ('registrada', 'anulada'))");
        DB::statement('ALTER TABLE operaciones ADD CONSTRAINT chk_operaciones_fechas_contrato CHECK (fecha_inicio_contrato IS NULL OR fecha_fin_contrato IS NULL OR fecha_fin_contrato >= fecha_inicio_contrato)');
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones');
    }
};
