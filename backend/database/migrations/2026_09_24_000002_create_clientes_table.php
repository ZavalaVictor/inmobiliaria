<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 80);
            $table->string('apellido_materno', 80)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('tipo_interes', 20)->nullable();
            $table->decimal('presupuesto_min', 14, 2)->nullable();
            $table->decimal('presupuesto_max', 14, 2)->nullable();
            $table->text('preferencias')->nullable();
            $table->string('estado_cliente', 20)->default('prospecto');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id', 'uq_clientes_user');
            $table->index('email', 'idx_clientes_email');
            $table->index('telefono', 'idx_clientes_telefono');
            $table->index('estado_cliente', 'idx_clientes_estado');
            $table->index('deleted_at', 'idx_clientes_deleted_at');
            $table->foreign('user_id', 'fk_clientes_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT chk_clientes_tipo_interes CHECK (tipo_interes IS NULL OR tipo_interes IN ('compra', 'renta', 'ambos'))");
        DB::statement("ALTER TABLE clientes ADD CONSTRAINT chk_clientes_estado CHECK (estado_cliente IN ('prospecto', 'cliente', 'inactivo'))");
        DB::statement('ALTER TABLE clientes ADD CONSTRAINT chk_clientes_presupuesto_min CHECK (presupuesto_min IS NULL OR presupuesto_min >= 0)');
        DB::statement('ALTER TABLE clientes ADD CONSTRAINT chk_clientes_presupuesto_max CHECK (presupuesto_max IS NULL OR presupuesto_max >= 0)');
        DB::statement('ALTER TABLE clientes ADD CONSTRAINT chk_clientes_rango_presupuesto CHECK (presupuesto_min IS NULL OR presupuesto_max IS NULL OR presupuesto_max >= presupuesto_min)');
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
