<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('series_number');
            $table->string('status', 20)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['game_room_id', 'series_number']);
            $table->index(['game_room_id', 'status']);
        });

        Schema::create('game_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_series_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('run_number');
            $table->string('status', 20)->default('running');
            $table->string('outcome_reason', 32)->nullable();
            $table->unsignedBigInteger('map_seed');
            $table->json('map_data');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['game_series_id', 'run_number']);
            $table->index(['game_series_id', 'status']);
        });

        Schema::create('game_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_run_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position_x');
            $table->unsignedTinyInteger('position_y');
            $table->string('heading', 2);
            $table->unsignedInteger('tick_number')->default(0);
            $table->unsignedInteger('moves_made')->default(0);
            $table->timestamp('next_tick_at');
            $table->timestamp('last_command_at')->nullable();
            $table->timestamps();
        });

        Schema::create('game_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('command_type', 32);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('superseded_by_command_id')->nullable()->constrained('game_commands')->nullOnDelete();
            $table->string('rejection_reason', 64)->nullable();
            $table->timestamps();
            $table->index(['game_run_id', 'status', 'submitted_at'], 'game_commands_run_status_submitted_idx');
        });

        Schema::create('game_ticks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tick_number');
            $table->unsignedTinyInteger('from_x');
            $table->unsignedTinyInteger('from_y');
            $table->smallInteger('attempted_x');
            $table->smallInteger('attempted_y');
            $table->string('heading_before', 2);
            $table->string('heading_after', 2);
            $table->string('outcome', 20);
            $table->string('end_reason', 32)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->unique(['game_run_id', 'tick_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_ticks');
        Schema::dropIfExists('game_commands');
        Schema::dropIfExists('game_states');
        Schema::dropIfExists('game_runs');
        Schema::dropIfExists('game_series');
    }
};
