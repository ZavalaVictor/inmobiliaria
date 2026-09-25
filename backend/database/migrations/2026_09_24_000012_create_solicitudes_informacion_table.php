<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_informacion', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('inmueble_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('atendida_por_user_id')->nullable();
            $table->string('nombre', 150);
            $table->string('email', 150);
            $table->string('telefono', 20)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('medio_preferido', 20)->nullable();
            $table->string('estado', 20)->default('nueva');
            $table->string('origen', 30)->default('landing_publica');
            $table->dateTime('fecha_solicitud')->useCurrent();
            $table->dateTime('fecha_atencion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('inmueble_id', 'idx_solicitudes_inmueble');
            $table->index('cliente_id', 'idx_solicitudes_cliente');
            $table->index('atendida_por_user_id', 'idx_solicitudes_usuario');
            $table->index('estado', 'idx_solicitudes_estado');
            $table->index('fecha_solicitud', 'idx_solicitudes_fecha');
            $table->index('deleted_at', 'idx_solicitudes_deleted_at');
            $table->foreign('cliente_id', 'fk_solicitudes_cliente')
                ->references('id')
                ->on('clientes')
                ->nullOnDelete();
            $table->foreign('inmueble_id', 'fk_solicitudes_inmueble')
                ->references('id')
                ->on('inmuebles')
                ->nullOnDelete();
            $table->foreign('atendida_por_user_id', 'fk_solicitudes_usuario')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE solicitudes_informacion ADD CONSTRAINT chk_solicitudes_medio CHECK (medio_preferido IS NULL OR medio_preferido IN ('telefono', 'whatsapp', 'correo'))");
        DB::statement("ALTER TABLE solicitudes_informacion ADD CONSTRAINT chk_solicitudes_estado CHECK (estado IN ('nueva', 'en_atencion', 'atendida', 'descartada'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_informacion');
    }
};
