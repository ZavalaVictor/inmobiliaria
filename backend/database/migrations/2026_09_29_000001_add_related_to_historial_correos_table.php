<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_correos', function (Blueprint $table): void {
            $table->string('relacionado_type')->nullable()->after('cita_id');
            $table->unsignedBigInteger('relacionado_id')->nullable()->after('relacionado_type');
            $table->index(['relacionado_type', 'relacionado_id'], 'idx_correos_relacionado');
        });
    }

    public function down(): void
    {
        Schema::table('historial_correos', function (Blueprint $table): void {
            $table->dropIndex('idx_correos_relacionado');
            $table->dropColumn(['relacionado_type', 'relacionado_id']);
        });
    }
};
