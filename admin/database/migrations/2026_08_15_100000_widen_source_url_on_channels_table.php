<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bir nechta manba havolasi (har biri alohida qatorda) sig'ishi uchun text'ga o'tkaziladi.
        Schema::table('channels', function (Blueprint $table) {
            $table->text('source_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('source_url', 2048)->nullable()->change();
        });
    }
};
