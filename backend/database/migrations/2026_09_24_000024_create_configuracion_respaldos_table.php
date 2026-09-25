<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_respaldos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('actualizado_por_user_id')->nullable();
            $table->boolean('activo')->default(false);
            $table->string('frecuencia', 20)->default('diario');
            $table->time('hora_ejecucion')->default('02:00:00');
            $table->unsignedTinyInteger('dia_semana')->nullable();
            $table->unsignedTinyInteger('dia_mes')->nullable();
            $table->unsignedSmallInteger('retencion_dias')->default(30);
            $table->string('ruta_destino', 500)->nullable();
            $table->dateTime('ultima_ejecucion_at')->nullable();
            $table->timestamps();

            $table->index('actualizado_por_user_id', 'idx_config_respaldos_usuario');
            $table->index('activo', 'idx_config_respaldos_activo');
            $table->foreign('actualizado_por_user_id', 'fk_config_respaldos_usuario')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_activo CHECK (activo IN (0, 1))');
        DB::statement("ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_frecuencia CHECK (frecuencia IN ('diario', 'semanal', 'mensual'))");
        DB::statement('ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_dia_semana CHECK (dia_semana IS NULL OR dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_dia_mes CHECK (dia_mes IS NULL OR dia_mes BETWEEN 1 AND 28)');
        DB::statement('ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_retencion CHECK (retencion_dias >= 1)');
        DB::statement("ALTER TABLE configuracion_respaldos ADD CONSTRAINT chk_config_respaldos_programacion CHECK ((frecuencia = 'diario' AND dia_semana IS NULL AND dia_mes IS NULL) OR (frecuencia = 'semanal' AND dia_semana IS NOT NULL AND dia_mes IS NULL) OR (frecuencia = 'mensual' AND dia_semana IS NULL AND dia_mes IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_respaldos');
    }
};
