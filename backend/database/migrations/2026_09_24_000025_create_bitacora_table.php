<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('accion', 50);
            $table->string('entidad', 80)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 500)->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->dateTime('fecha_evento')->useCurrent();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'idx_bitacora_usuario');
            $table->index('accion', 'idx_bitacora_accion');
            $table->index(['entidad', 'entidad_id'], 'idx_bitacora_entidad');
            $table->index('fecha_evento', 'idx_bitacora_fecha');
            $table->foreign('user_id', 'fk_bitacora_usuario')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
