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
        Schema::create('company_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('google_drive_folder_id'); // ID da pasta no Google Drive
            $table->string('folder_name'); // Nome da pasta para referência
            $table->text('description')->nullable(); // Descrição da pasta
            $table->boolean('active')->default(true); // Se a permissão está ativa
            $table->timestamps();

            // Índices
            $table->index(['company_id', 'google_drive_folder_id']);
            $table->unique(['company_id', 'google_drive_folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_folders');
    }
};
