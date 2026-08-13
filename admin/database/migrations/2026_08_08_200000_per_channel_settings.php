<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('channels', 'source_mode')) {
            Schema::table('channels', function (Blueprint $table) {
                $table->string('source_mode', 20)->default('both');
                $table->boolean('auto_send')->default(false);
                $table->boolean('schedule_enabled')->default(true);
                $table->string('schedule_time', 255)->default('09:00');
                $table->text('ai_prompt')->nullable();
            });
        }

        if (!Schema::hasColumn('sources', 'channel_id')) {
            Schema::table('sources', function (Blueprint $table) {
                $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('posts', 'channel_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });
        Schema::table('sources', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['source_mode', 'auto_send', 'schedule_enabled', 'schedule_time', 'ai_prompt']);
        });
    }
};
