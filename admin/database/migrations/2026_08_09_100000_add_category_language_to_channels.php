<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('channels', 'category')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->text('category')->nullable()->after('ai_prompt');
                $table->string('language', 20)->default('uz_latin')->after('category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['category', 'language']);
        });
    }
};
