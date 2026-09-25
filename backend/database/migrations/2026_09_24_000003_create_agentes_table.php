<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agentes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('numero_empleado', 30);
            $table->string('telefono_corporativo', 20)->nullable();
            $table->string('zona_asignacion', 100)->nullable();
            $table->text('horario')->nullable();
            $table->decimal('porcentaje_comision', 5, 2)->default(0.00);
            $table->string('foto_path', 500)->nullable();
            $table->string('estado_laboral', 20)->default('activo');
            $table->date('fecha_contratacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id', 'uq_agentes_user');
            $table->unique('numero_empleado', 'uq_agentes_numero_empleado');
            $table->index('estado_laboral', 'idx_agentes_estado');
            $table->index('zona_asignacion', 'idx_agentes_zona');
            $table->index('deleted_at', 'idx_agentes_deleted_at');
            $table->foreign('user_id', 'fk_agentes_user')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE agentes ADD CONSTRAINT chk_agentes_estado CHECK (estado_laboral IN ('activo', 'inactivo'))");
        DB::statement('ALTER TABLE agentes ADD CONSTRAINT chk_agentes_comision CHECK (porcentaje_comision >= 0 AND porcentaje_comision <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('agentes');
    }
};
