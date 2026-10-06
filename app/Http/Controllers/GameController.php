<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\PlayerAction;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use App\Services\Lobby\RoomPlayerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function start(Request $request, RoomPlayerContext $context): RedirectResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer && $roomPlayer->is_host, 403);

        $room = $roomPlayer->gameRoom()->lockForUpdate()->firstOrFail();

        if ($room->status !== 'waiting') {
            return redirect()->route('game');
        }

        $captainRoleId = Role::query()->where('slug', 'captain')->value('id');
        $helmsmanRoleId = Role::query()->where('slug', 'helmsman')->value('id');
        $captainAssigned = RoomPlayerRole::query()
            ->where('game_room_id', $room->id)
            ->where('role_id', $captainRoleId)
            ->exists();
        $helmsmanAssigned = RoomPlayerRole::query()
            ->where('game_room_id', $room->id)
            ->where('role_id', $helmsmanRoleId)
            ->exists();

        if (! $captainAssigned || ! $helmsmanAssigned) {
            return redirect()->route('lobby')->with('error', 'Aby uruchomić rozgrywkę, kapitan i sternik muszą być już obsadzeni.');
        }

        $room->update(['status' => 'playing', 'last_activity_at' => now()]);

        PlayerAction::create([
            'player_id' => $roomPlayer->player_id,
            'game_room_id' => $room->id,
            'room_player_id' => $roomPlayer->id,
            'action' => 'game.start',
            'outcome' => 'started',
            'details' => ['captain' => $captainAssigned, 'helmsman' => $helmsmanAssigned],
            'ip_hash' => $this->ipHash($request),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('game');
    }

    public function index(Request $request, RoomPlayerContext $context): View
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer, 403);

        $room = $roomPlayer->gameRoom()->with(['players.roleAssignment.role'])->firstOrFail();
        abort_unless($room->status === 'playing', 404);

        $effectiveRole = $context->effectiveRole($request, $roomPlayer);
        $shipPosition = ['x' => 4, 'y' => 9];
        $target = ['x' => 16, 'y' => 15];
        $obstacles = [
            [2, 4], [3, 4], [4, 4], [11, 7], [11, 8], [11, 9], [11, 10], [12, 10], [13, 10], [14, 10],
            [7, 12], [8, 12], [9, 12], [10, 12], [15, 4], [15, 5], [15, 6], [16, 7], [17, 7], [18, 7],
        ];

        return view('game', [
            'room' => $room,
            'roomPlayer' => $roomPlayer,
            'effectiveRole' => $effectiveRole,
            'shipPosition' => $shipPosition,
            'target' => $target,
            'obstacles' => $obstacles,
            'mapSize' => 20,
            'isHost' => $roomPlayer->is_host,
        ]);
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }
}
