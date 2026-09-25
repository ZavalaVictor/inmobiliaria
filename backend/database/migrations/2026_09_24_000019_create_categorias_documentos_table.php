<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_documentos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nombre', 'uq_categorias_documentos_nombre');
            $table->index('activo', 'idx_categorias_documentos_activo');
            $table->index('deleted_at', 'idx_categorias_documentos_deleted_at');
        });

        DB::statement('ALTER TABLE categorias_documentos ADD CONSTRAINT chk_categorias_documentos_activo CHECK (activo IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_documentos');
    }
};
