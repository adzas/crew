<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->text('reason');
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['player_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_bans');
    }
};
