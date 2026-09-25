<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('tipo_persona', 10)->default('fisica');
            $table->string('nombre_razon_social', 150);
            $table->string('rfc', 13);
            $table->string('telefono', 20);
            $table->string('email', 150)->nullable();
            $table->string('direccion', 255);
            $table->string('estado_registro', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('rfc', 'uq_propietarios_rfc');
            $table->index('nombre_razon_social', 'idx_propietarios_nombre');
            $table->index('estado_registro', 'idx_propietarios_estado');
            $table->index('deleted_at', 'idx_propietarios_deleted_at');
        });

        DB::statement("ALTER TABLE propietarios ADD CONSTRAINT chk_propietarios_tipo CHECK (tipo_persona IN ('fisica', 'moral'))");
        DB::statement("ALTER TABLE propietarios ADD CONSTRAINT chk_propietarios_estado CHECK (estado_registro IN ('activo', 'inactivo'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};
