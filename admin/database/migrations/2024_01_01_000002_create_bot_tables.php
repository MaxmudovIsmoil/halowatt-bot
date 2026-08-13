<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('chat_id')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sources')) {
            Schema::create('sources', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('url');
                $table->string('type', 20)->default('rss');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->text('content');
                $table->string('source_type', 20)->default('ai');
                $table->string('status', 20)->default('pending');
                $table->text('error')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('post_channel')) {
            Schema::create('post_channel', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained()->cascadeOnDelete();
                $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20)->default('pending');
                $table->text('error')->nullable();
                $table->bigInteger('message_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_channel');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('channels');
    }
};
