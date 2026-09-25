<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visualizaciones_inmuebles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('inmueble_id');
            $table->string('session_id', 100)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referer', 500)->nullable();
            $table->string('origen', 30)->default('landing_publica');
            $table->dateTime('fecha_visualizacion')->useCurrent();
            $table->timestamp('created_at')->nullable();

            $table->index('inmueble_id', 'idx_visualizaciones_inmueble');
            $table->index('fecha_visualizacion', 'idx_visualizaciones_fecha');
            $table->index(['inmueble_id', 'fecha_visualizacion'], 'idx_visualizaciones_inmueble_fecha');
            $table->index('origen', 'idx_visualizaciones_origen');
            $table->foreign('inmueble_id', 'fk_visualizaciones_inmueble')->references('id')->on('inmuebles');
        });

        DB::statement("ALTER TABLE visualizaciones_inmuebles ADD CONSTRAINT chk_visualizaciones_origen CHECK (origen IN ('landing_publica', 'portal_cliente', 'interno'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('visualizaciones_inmuebles');
    }
};
