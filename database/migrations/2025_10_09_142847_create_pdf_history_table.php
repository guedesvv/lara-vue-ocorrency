<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_history', function (Blueprint $table) {
            $table->id();
            $table->string('ocorrencyId', 500);
            $table->string('pdf_path', 500);
            $table->dateTime('uploadDate');
            $table->string('evidencyUploader', 500)->nullable();
            $table->string('reason', 500)->nullable();
            $table->dateTime('reasonDateTime')->nullable();
            $table->string('evidencyApprover', 500)->nullable();
            $table->timestamps(); // cria created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_history');
    }
};
