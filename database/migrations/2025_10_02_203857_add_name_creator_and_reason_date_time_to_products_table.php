<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('nameCreator', 50)->nullable()->after('nome_da_coluna_antes');
            $table->dateTime('reasonDateTime')->nullable()->after('nameCreator');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['nameCreator', 'reasonDateTime']);
        });
    }
};
