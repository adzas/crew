<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'player_id', 'game_room_id', 'room_player_id', 'action', 'outcome', 'details', 'ip_hash', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }
}
