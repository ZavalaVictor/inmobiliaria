<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respaldos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('generado_por_user_id')->nullable();
            $table->unsignedBigInteger('restaurado_por_user_id')->nullable();
            $table->string('tipo', 20)->default('manual');
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->dateTime('fecha_inicio')->useCurrent();
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->dateTime('restaurado_at')->nullable();
            $table->string('estado_restauracion', 20)->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->index('generado_por_user_id', 'idx_respaldos_generado_por');
            $table->index('restaurado_por_user_id', 'idx_respaldos_restaurado_por');
            $table->index('tipo', 'idx_respaldos_tipo');
            $table->index('estado', 'idx_respaldos_estado');
            $table->index('fecha_inicio', 'idx_respaldos_fecha');
            $table->foreign('generado_por_user_id', 'fk_respaldos_generado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('restaurado_por_user_id', 'fk_respaldos_restaurado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE respaldos ADD CONSTRAINT chk_respaldos_tipo CHECK (tipo IN ('manual', 'automatico'))");
        DB::statement("ALTER TABLE respaldos ADD CONSTRAINT chk_respaldos_estado CHECK (estado IN ('pendiente', 'en_proceso', 'completado', 'fallido'))");
        DB::statement("ALTER TABLE respaldos ADD CONSTRAINT chk_respaldos_estado_restauracion CHECK (estado_restauracion IS NULL OR estado_restauracion IN ('en_proceso', 'completada', 'fallida'))");
        DB::statement('ALTER TABLE respaldos ADD CONSTRAINT chk_respaldos_fechas CHECK (fecha_finalizacion IS NULL OR fecha_finalizacion >= fecha_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('respaldos');
    }
};
