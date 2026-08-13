<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('channels', 'ai_provider')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->string('ai_provider', 20)->default('claude')->after('source_mode');
            });
        }
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('ai_provider');
        });
    }
};
