<?php

namespace App\Services\Lobby;

use App\Models\Player;
use App\Models\Role;
use App\Models\RoomPlayer;
use Illuminate\Http\Request;

class RoomPlayerContext
{
    public function current(Request $request): ?RoomPlayer
    {
        $sessionToken = $request->session()->get('player_token');
        $roomPlayerId = $request->session()->get('room_player_id');

        if (! is_string($sessionToken) || $sessionToken === '' || ! is_numeric($roomPlayerId)) {
            return null;
        }

        $player = Player::query()
            ->where('session_key_hash', hash_hmac('sha256', $sessionToken, (string) config('app.key')))
            ->first();

        if (! $player) {
            return null;
        }

        return RoomPlayer::query()
            ->with(['player', 'gameRoom', 'roleAssignment.role'])
            ->whereKey($roomPlayerId)
            ->where('player_id', $player->id)
            ->first();
    }

    public function dopplerEnabled(): bool
    {
        return (bool) config('game.doppler.enabled')
            && in_array((string) config('app.env'), ['local', 'testing'], true);
    }

    public function dopplerRole(Request $request, ?RoomPlayer $roomPlayer): ?Role
    {
        if (! $this->dopplerEnabled() || ! $roomPlayer?->is_host) {
            return null;
        }

        $roleId = $request->session()->get('doppler_role_id');
        $hostRoomPlayerId = $request->session()->get('doppler_room_player_id');
        if (! is_numeric($roleId) || (int) $hostRoomPlayerId !== $roomPlayer->id) {
            $request->session()->forget(['doppler_role_id', 'doppler_room_player_id']);

            return null;
        }

        $role = Role::query()->whereKey($roleId)->where('is_active', true)->first();
        if (! $role) {
            $request->session()->forget(['doppler_role_id', 'doppler_room_player_id']);
        }

        return $role;
    }

    public function effectiveRole(Request $request, ?RoomPlayer $roomPlayer): ?Role
    {
        return $this->dopplerRole($request, $roomPlayer)
            ?? $roomPlayer?->roleAssignment?->role;
    }
}
