<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameTick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'game_run_id', 'tick_number', 'from_x', 'from_y', 'attempted_x', 'attempted_y', 'heading_before', 'heading_after', 'outcome', 'end_reason', 'details', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'processed_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(GameRun::class, 'game_run_id');
    }
}
