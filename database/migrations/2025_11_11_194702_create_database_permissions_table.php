<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('database_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('connection_id')->constrained()->onDelete('cascade');

            // O nome do banco de dados que o usuário PODE ver
            $table->string('database_name');

            $table->timestamps();

            // Garante que a permissão é única
            $table->unique(['user_id', 'connection_id', 'database_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_permissions');
    }
};
