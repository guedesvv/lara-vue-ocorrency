<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('evidencyApprover', 50)->nullable()->after('confirmEvidency');
            $table->string('evidencyUpploader', 50)->nullable()->after('evidencyApprover');
            $table->dateTime('lastPdfUpload')->nullable()->after('evidencyUpploader');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['evidencyApprover', 'evidencyUpploader', 'lastPdfUpload']);
        });
    }
};
