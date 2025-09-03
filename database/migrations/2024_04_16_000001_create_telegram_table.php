<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_dialog_messages', function (Blueprint $table) {
            $table->id();
            $table->json('keywords');
            $table->text('detail', 1000);
            $table->integer('views')->default(0);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_visit_counters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('one_time')->default(0);
            $table->integer('daily')->default(0);
            $table->integer('monthly')->default(0);
            $table->integer('yearly')->default(0);
            $table->datetime('last_date')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_media_counters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('text')->default(0);
            $table->integer('photo')->default(0);
            $table->integer('audio')->default(0);
            $table->integer('video')->default(0);
            $table->integer('document')->default(0);
            $table->datetime('last_date')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_callbacks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('reply_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_cache_inputs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('command')->nullable();
            $table->json('steps')->nullable();
            $table->string('active_step')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_cache_sliders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('label')->nullable();
            $table->string('command')->nullable();
            $table->string('first_step')->nullable();
            $table->string('previous_step')->nullable();
            $table->string('active_step')->nullable();
            $table->string('next_step')->nullable();
            $table->string('last_step')->nullable();
            $table->json('steps_info')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_reviews', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('note');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_admins', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('detail')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_media', function (Blueprint $table) {
            $table->id();
            $table->string('file_id');
            $table->string('unique_id')->nullable();
            $table->string('path')->nullable();
            $table->string('local_path')->nullable();
            $table->string('size')->nullable();
            $table->string('mime')->nullable();
            $table->string('name')->nullable();
            $table->string('disc')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_media_detail', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('bot_direct_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message', 1000);
            $table->timestamps();
        });

        Schema::create('bot_dm_responses', function (Blueprint $table) {
            $table->id();
            $table->text('message', 1000);
            $table->timestamps();
        });

        Schema::create('bot_broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('label');
            $table->text('detail', 1000);
            $table->boolean('can_repeat');
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_broadcast_seen_users', function (Blueprint $table) {
            $table->id();
            $table->text('comment', 1000);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_broadcast_locked_users', function (Blueprint $table) {
            $table->id();
            $table->text('comment', 1000);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_parents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('regs')->nullable();
            $table->boolean('report_active')->default(true);;
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_candidates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('reg')->nullable();
            $table->boolean('report_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_teachers_media', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('data');
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_app_info', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('label');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_emoji_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->string('message_id');
            $table->string('type')->nullable();
            $table->string('emoji')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_settings_auths', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->json('auths')->nullable();
            $table->json('users')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_settings_switch', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->boolean('is_active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_dialog_messages');
        Schema::dropIfExists('bot_broadcast_seen_users');
        Schema::dropIfExists('bot_broadcast_locked_users');
        Schema::dropIfExists('bot_broadcast_messages');
        Schema::dropIfExists('bot_dm_responses');
        Schema::dropIfExists('bot_direct_messages');
        Schema::dropIfExists('bot_visit_counters');
        Schema::dropIfExists('bot_media_counters');
        Schema::dropIfExists('bot_callbacks');
        Schema::dropIfExists('bot_cache_inputs');
        Schema::dropIfExists('bot_cache_sliders');
        Schema::dropIfExists('bot_reviews');
        Schema::dropIfExists('bot_app_info');
        Schema::dropIfExists('bot_snippets');
        Schema::dropIfExists('bot_emoji_reactions');
        Schema::dropIfExists('bot_settings');
        Schema::dropIfExists('bot_candidates');
        Schema::dropIfExists('bot_parents');
        Schema::dropIfExists('bot_teachers_media');
        Schema::dropIfExists('bot_media_detail');
        Schema::dropIfExists('bot_media');
        Schema::dropIfExists('bot_admins');
        Schema::dropIfExists('bot_settings_auths');
        Schema::dropIfExists('bot_settings_switch');
    }
};
