<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameCommand extends Model
{
    protected $fillable = [
        'game_run_id', 'player_id', 'room_player_id', 'role_id', 'command_type', 'payload', 'status', 'submitted_at', 'processed_at', 'superseded_by_command_id', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'submitted_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(GameRun::class, 'game_run_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function roomPlayer(): BelongsTo
    {
        return $this->belongsTo(RoomPlayer::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_command_id');
    }
}
