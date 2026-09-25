<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('propietario_id');
            $table->unsignedBigInteger('categoria_id');
            $table->string('codigo', 30);
            $table->string('titulo', 150);
            $table->string('slug', 180);
            $table->text('descripcion')->nullable();
            $table->string('tipo_operacion', 20);
            $table->decimal('precio_venta', 14, 2)->nullable();
            $table->decimal('renta_mensual', 14, 2)->nullable();
            $table->decimal('superficie_terreno_m2', 10, 2)->nullable();
            $table->decimal('superficie_construccion_m2', 10, 2)->nullable();
            $table->unsignedTinyInteger('habitaciones')->default(0);
            $table->unsignedTinyInteger('banos_completos')->default(0);
            $table->unsignedTinyInteger('medios_banos')->default(0);
            $table->unsignedTinyInteger('estacionamientos')->default(0);
            $table->unsignedTinyInteger('niveles')->default(1);
            $table->string('calle', 150);
            $table->string('numero_exterior', 20)->nullable();
            $table->string('numero_interior', 20)->nullable();
            $table->string('colonia', 100);
            $table->string('municipio', 100);
            $table->string('estado_ubicacion', 100);
            $table->string('codigo_postal', 10);
            $table->string('referencias', 255)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('estado_disponibilidad', 20)->default('disponible');
            $table->boolean('publicado')->default(false);
            $table->dateTime('fecha_publicacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('codigo', 'uq_inmuebles_codigo');
            $table->unique('slug', 'uq_inmuebles_slug');
            $table->index('propietario_id', 'idx_inmuebles_propietario');
            $table->index('categoria_id', 'idx_inmuebles_categoria');
            $table->index('tipo_operacion', 'idx_inmuebles_tipo_operacion');
            $table->index('estado_disponibilidad', 'idx_inmuebles_estado');
            $table->index('publicado', 'idx_inmuebles_publicado');
            $table->index(['estado_ubicacion', 'municipio', 'colonia'], 'idx_inmuebles_ubicacion');
            $table->index('deleted_at', 'idx_inmuebles_deleted_at');
            $table->foreign('propietario_id', 'fk_inmuebles_propietario')->references('id')->on('propietarios');
            $table->foreign('categoria_id', 'fk_inmuebles_categoria')->references('id')->on('categorias');
        });

        DB::statement("ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_tipo_operacion CHECK (tipo_operacion IN ('venta', 'renta'))");
        DB::statement("ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_estado CHECK (estado_disponibilidad IN ('disponible', 'vendido', 'rentado', 'inactivo'))");
        DB::statement('ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_publicado CHECK (publicado IN (0, 1))');
        DB::statement("ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_precios CHECK ((tipo_operacion = 'venta' AND precio_venta IS NOT NULL AND precio_venta > 0 AND renta_mensual IS NULL) OR (tipo_operacion = 'renta' AND renta_mensual IS NOT NULL AND renta_mensual > 0 AND precio_venta IS NULL))");
        DB::statement('ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_superficie_terreno CHECK (superficie_terreno_m2 IS NULL OR superficie_terreno_m2 > 0)');
        DB::statement('ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_superficie_construccion CHECK (superficie_construccion_m2 IS NULL OR superficie_construccion_m2 >= 0)');
        DB::statement('ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_latitud CHECK (latitud IS NULL OR (latitud >= -90 AND latitud <= 90))');
        DB::statement('ALTER TABLE inmuebles ADD CONSTRAINT chk_inmuebles_longitud CHECK (longitud IS NULL OR (longitud >= -180 AND longitud <= 180))');
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
