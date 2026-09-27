<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->char('session_key_hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->unique();
            $table->string('status', 20)->default('waiting')->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 24);
            $table->boolean('is_host')->default(false);
            $table->timestamp('joined_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['game_room_id', 'player_id']);
            $table->index(['game_room_id', 'joined_at']);
        });

        Schema::create('player_join_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_player_id')->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('joined_at')->index();
        });

        Schema::create('room_player_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->unique(['game_room_id', 'room_player_id']);
            $table->unique(['game_room_id', 'role_id']);
        });

        Schema::create('player_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('outcome', 24)->index();
            $table->json('details')->nullable();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_actions');
        Schema::dropIfExists('room_player_roles');
        Schema::dropIfExists('player_join_logs');
        Schema::dropIfExists('room_players');
        Schema::dropIfExists('game_rooms');
        Schema::dropIfExists('players');
        Schema::dropIfExists('roles');
    }
};
