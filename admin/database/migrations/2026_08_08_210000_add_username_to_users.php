<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('name');
            });

            DB::table('users')->whereNull('username')->update([
                'username' => DB::raw("regexp_replace(lower(name), '[^a-z0-9]', '', 'g')"),
            ]);

            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
