<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            $table->string('trend', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scan_results', function (Blueprint $table) {
            $table->string('trend', 30)->nullable()->change();
        });
    }
};
