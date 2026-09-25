<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_correos', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('destinatario_user_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('cita_id')->nullable();
            $table->unsignedBigInteger('enviado_por_user_id')->nullable();
            $table->string('destinatario_email', 150);
            $table->string('destinatario_nombre', 150)->nullable();
            $table->string('tipo', 40);
            $table->string('asunto', 255);
            $table->string('plantilla', 100)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('proveedor_message_id', 255)->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();

            $table->index('destinatario_user_id', 'idx_correos_destinatario_user');
            $table->index('cliente_id', 'idx_correos_cliente');
            $table->index('cita_id', 'idx_correos_cita');
            $table->index('enviado_por_user_id', 'idx_correos_enviado_por');
            $table->index('destinatario_email', 'idx_correos_email');
            $table->index('tipo', 'idx_correos_tipo');
            $table->index('estado', 'idx_correos_estado');
            $table->index('fecha_envio', 'idx_correos_fecha');
            $table->foreign('cita_id', 'fk_correos_cita')
                ->references('id')
                ->on('citas')
                ->nullOnDelete();
            $table->foreign('cliente_id', 'fk_correos_cliente')
                ->references('id')
                ->on('clientes')
                ->nullOnDelete();
            $table->foreign('destinatario_user_id', 'fk_correos_destinatario_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('enviado_por_user_id', 'fk_correos_enviado_por')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE historial_correos ADD CONSTRAINT chk_correos_tipo CHECK (tipo IN ('confirmacion_cita', 'reprogramacion_cita', 'cancelacion_cita', 'recuperacion_password', 'respaldo', 'restauracion', 'seguridad', 'otro'))");
        DB::statement("ALTER TABLE historial_correos ADD CONSTRAINT chk_correos_estado CHECK (estado IN ('pendiente', 'enviado', 'fallido'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_correos');
    }
};
