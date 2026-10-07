<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameState extends Model
{
    protected $fillable = [
        'game_run_id', 'position_x', 'position_y', 'heading', 'tick_number', 'moves_made', 'next_tick_at', 'last_command_at',
    ];

    protected function casts(): array
    {
        return ['next_tick_at' => 'datetime', 'last_command_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(GameRun::class, 'game_run_id');
    }
}
