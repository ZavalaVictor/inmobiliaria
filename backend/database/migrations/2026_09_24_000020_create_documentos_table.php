<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('categoria_documento_id');
            $table->unsignedBigInteger('subido_por_user_id');
            $table->unsignedBigInteger('propietario_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('inmueble_id')->nullable();
            $table->unsignedBigInteger('operacion_id')->nullable();
            $table->string('nombre_original', 255);
            $table->string('firebase_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->date('fecha_documento')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('categoria_documento_id', 'idx_documentos_categoria');
            $table->index('subido_por_user_id', 'idx_documentos_usuario');
            $table->index('propietario_id', 'idx_documentos_propietario');
            $table->index('cliente_id', 'idx_documentos_cliente');
            $table->index('inmueble_id', 'idx_documentos_inmueble');
            $table->index('operacion_id', 'idx_documentos_operacion');
            $table->index('deleted_at', 'idx_documentos_deleted_at');
            $table->foreign('categoria_documento_id', 'fk_documentos_categoria')->references('id')->on('categorias_documentos');
            $table->foreign('cliente_id', 'fk_documentos_cliente')->references('id')->on('clientes');
            $table->foreign('inmueble_id', 'fk_documentos_inmueble')->references('id')->on('inmuebles');
            $table->foreign('operacion_id', 'fk_documentos_operacion')->references('id')->on('operaciones');
            $table->foreign('propietario_id', 'fk_documentos_propietario')->references('id')->on('propietarios');
            $table->foreign('subido_por_user_id', 'fk_documentos_usuario')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE documentos ADD CONSTRAINT chk_documentos_tipo_archivo CHECK (mime_type IN ('application/pdf', 'image/png', 'image/jpeg'))");
        DB::statement('ALTER TABLE documentos ADD CONSTRAINT chk_documentos_destino CHECK ((propietario_id IS NOT NULL) + (cliente_id IS NOT NULL) + (inmueble_id IS NOT NULL) + (operacion_id IS NOT NULL) = 1)');
        DB::statement('ALTER TABLE documentos ADD CONSTRAINT chk_documentos_fechas CHECK (fecha_documento IS NULL OR fecha_vencimiento IS NULL OR fecha_vencimiento >= fecha_documento)');
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
