<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerJoinLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['player_id', 'game_room_id', 'room_player_id', 'ip_hash', 'user_agent', 'joined_at'];
}
