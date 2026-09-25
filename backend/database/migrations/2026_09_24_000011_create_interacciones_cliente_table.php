<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacciones_cliente', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('registrado_por_user_id');
            $table->string('tipo', 30);
            $table->text('descripcion');
            $table->string('resultado', 255)->nullable();
            $table->dateTime('fecha_interaccion')->useCurrent();
            $table->string('proxima_accion', 255)->nullable();
            $table->dateTime('fecha_proxima_accion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id', 'idx_interacciones_cliente');
            $table->index('registrado_por_user_id', 'idx_interacciones_usuario');
            $table->index('tipo', 'idx_interacciones_tipo');
            $table->index('fecha_interaccion', 'idx_interacciones_fecha');
            $table->index('fecha_proxima_accion', 'idx_interacciones_proxima_fecha');
            $table->index('deleted_at', 'idx_interacciones_deleted_at');
            $table->foreign('cliente_id', 'fk_interacciones_cliente')->references('id')->on('clientes');
            $table->foreign('registrado_por_user_id', 'fk_interacciones_usuario')->references('id')->on('users');
        });

        DB::statement("ALTER TABLE interacciones_cliente ADD CONSTRAINT chk_interacciones_tipo CHECK (tipo IN ('llamada', 'correo', 'whatsapp', 'reunion', 'nota', 'seguimiento'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('interacciones_cliente');
    }
};
