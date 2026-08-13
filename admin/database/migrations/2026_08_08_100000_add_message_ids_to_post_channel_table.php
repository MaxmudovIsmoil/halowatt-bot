<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('post_channel', 'message_ids')) {
            Schema::table('post_channel', function (Blueprint $table) {
                $table->text('message_ids')->nullable()->after('message_id');
            });

            DB::statement("UPDATE post_channel SET message_ids = message_id::text WHERE message_ids IS NULL AND message_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('post_channel', function (Blueprint $table) {
            $table->dropColumn('message_ids');
        });
    }
};
