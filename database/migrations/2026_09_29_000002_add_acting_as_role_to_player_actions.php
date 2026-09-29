<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_actions', function (Blueprint $table) {
            $table->foreignId('acting_as_role_id')->nullable()->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('player_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acting_as_role_id');
        });
    }
};
