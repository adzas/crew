<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RoomPlayer extends Model
{
    protected $fillable = ['game_room_id', 'player_id', 'display_name', 'is_host', 'joined_at', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'is_host' => 'boolean',
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function roleAssignment(): HasOne
    {
        return $this->hasOne(RoomPlayerRole::class);
    }
}
