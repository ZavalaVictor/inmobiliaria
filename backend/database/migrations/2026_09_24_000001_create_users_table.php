<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 80);
            $table->string('apellido_materno', 80)->nullable();
            $table->string('email', 150);
            $table->string('telefono', 20)->nullable();
            $table->string('password', 255);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('email_verified_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('email', 'uq_users_email');
            $table->index('estado', 'idx_users_estado');
            $table->index('deleted_at', 'idx_users_deleted_at');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_estado CHECK (estado IN ('pendiente', 'activo', 'bloqueado', 'inactivo'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
