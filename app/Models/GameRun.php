<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameRun extends Model
{
    protected $fillable = [
        'game_series_id', 'run_number', 'status', 'outcome_reason', 'map_seed', 'map_data', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return ['map_data' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(GameSeries::class, 'game_series_id');
    }

    public function state(): HasOne
    {
        return $this->hasOne(GameState::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(GameCommand::class);
    }

    public function ticks(): HasMany
    {
        return $this->hasMany(GameTick::class);
    }
}
