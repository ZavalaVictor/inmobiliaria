<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmueble_imagenes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('inmueble_id');
            $table->string('firebase_path', 500);
            $table->string('url_publica', 1000)->nullable();
            $table->string('nombre_original', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('texto_alternativo', 180)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('inmueble_id', 'idx_imagenes_inmueble');
            $table->index(['inmueble_id', 'es_principal'], 'idx_imagenes_principal');
            $table->index(['inmueble_id', 'orden'], 'idx_imagenes_orden');
            $table->index('deleted_at', 'idx_imagenes_deleted_at');
            $table->foreign('inmueble_id', 'fk_imagenes_inmueble')
                ->references('id')
                ->on('inmuebles')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE inmueble_imagenes ADD CONSTRAINT chk_imagenes_principal CHECK (es_principal IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('inmueble_imagenes');
    }
};
