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
        Schema::table('bot_visit_counters', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_media_counters', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_callbacks', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_cache_inputs', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_cache_sliders', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_reviews', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignUuid('approved_by')->nullable()->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'approved_by']);
        });

        Schema::table('bot_admins', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignUuid('approved_by')->nullable()->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'approved_by']);
        });

        Schema::table('bot_media_detail', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id']);
        });

        Schema::table('bot_media', function (Blueprint $table) {
            $table->foreignId('media_detail_id')->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'media_detail_id']);
        });

        Schema::table('bot_parents', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_candidates', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_teachers_media', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_direct_messages', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id']);
        });

        Schema::table('bot_dm_responses', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('message_id')->constrained('bot_direct_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id', 'message_id']);
        });

        Schema::table('bot_broadcast_messages', function (Blueprint $table) {
            $table->foreignUuid('admin_id')->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'admin_id', 'media_id']);
        });

        Schema::table('bot_broadcast_seen_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('broadcast_id')->constrained('bot_broadcast_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'broadcast_id']);
        });

        Schema::table('bot_broadcast_locked_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('broadcast_id')->constrained('bot_broadcast_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'broadcast_id']);
        });

        Schema::table('bot_snippets', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_app_info', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('snippet_id')->nullable()->constrained('bot_snippets')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_emoji_reactions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_dm_responses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['message_id']);
            $table->dropForeign(['media_id']);
        });

        Schema::table('bot_direct_messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('bot_broadcast_seen_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['broadcast_id']);
        });

        Schema::table('bot_broadcast_locked_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['broadcast_id']);
        });

        Schema::table('bot_broadcast_messages', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropForeign(['media_id']);
        });

        Schema::table('bot_visit_counters', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_media_counters', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_callbacks', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_cache_inputs', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_cache_sliders', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_reviews', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_app_info', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
            $table->dropForeign(['snippet_id']);
        });

        Schema::table('bot_snippets', function (Blueprint $table) {
            $table->dropForeign(['media_id']);
        });

        Schema::table('bot_parents', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_candidates', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_media', function (Blueprint $table) {
            $table->dropForeign(['media_detail_id']);
        });

        Schema::table('bot_media_detail', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('bot_admins', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });

        Schema::table('bot_teachers_media', function (Blueprint $table) {
            $table->dropForeign(['id']);
        });
        


    }
};
