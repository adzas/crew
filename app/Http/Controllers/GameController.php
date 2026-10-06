<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\PlayerAction;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use App\Services\Lobby\RoomPlayerContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GameController extends Controller
{
    public function start(Request $request, RoomPlayerContext $context): RedirectResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer && $roomPlayer->is_host, 403);

        $room = GameRoom::query()
            ->whereKey($roomPlayer->game_room_id)
            ->lockForUpdate()
            ->firstOrFail();

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
        abort_unless($roomPlayer !== null, 403);

        $room = $roomPlayer->gameRoom()->with(['players.roleAssignment.role'])->firstOrFail();
        abort_unless($room->status === 'playing', 404);

        $effectiveRole = $context->effectiveRole($request, $roomPlayer);
        $isHelmsman = $effectiveRole?->slug === 'helmsman';
        $isCaptain = $effectiveRole?->slug === 'captain';
        $allowedDirections = [
            'N' => ['N', 'NE', 'NW'],
            'NE' => ['N', 'E'],
            'E' => ['NE', 'E', 'SE'],
            'SE' => ['E', 'S'],
            'S' => ['SE', 'S', 'SW'],
            'SW' => ['S', 'W'],
            'W' => ['NW', 'W', 'SW'],
            'NW' => ['N', 'W'],
        ];
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
            'isHelmsman' => $isHelmsman,
            'isCaptain' => $isCaptain,
            'roles' => Role::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'selectedDirection' => 'SE',
            'allowedDirections' => $allowedDirections['SE'],
        ]);
    }

    public function status(Request $request, RoomPlayerContext $context): JsonResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer !== null, 403);

        return response()->json(['status' => $roomPlayer->gameRoom->status])
            ->header('Cache-Control', 'no-store');
    }

    public function assignRole(Request $request, RoomPlayerContext $context, RoomPlayer $target): RedirectResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer && $roomPlayer->gameRoom->status === 'playing', 403);
        abort_unless($roomPlayer->is_host, 403);
        abort_unless($target->game_room_id === $roomPlayer->game_room_id, 404);

        $validated = $request->validate([
            'role' => ['required', 'string', 'exists:roles,slug'],
        ]);

        $result = DB::transaction(function () use ($request, $roomPlayer, $target, $validated) {
            $room = GameRoom::query()->whereKey($roomPlayer->game_room_id)->lockForUpdate()->firstOrFail();
            abort_unless($room->status === 'playing', 403);

            $role = Role::query()->where('slug', $validated['role'])->where('is_active', true)->firstOrFail();
            $previousHolder = RoomPlayerRole::query()
                ->where('game_room_id', $room->id)
                ->where('role_id', $role->id)
                ->where('room_player_id', '!=', $target->id)
                ->first();

            if ($previousHolder) {
                $previousHolder->delete();
            }

            RoomPlayerRole::updateOrCreate(
                ['game_room_id' => $room->id, 'room_player_id' => $target->id],
                ['role_id' => $role->id, 'assigned_at' => now()],
            );

            $room->update(['last_activity_at' => now()]);
            PlayerAction::create([
                'player_id' => $roomPlayer->player_id,
                'game_room_id' => $room->id,
                'room_player_id' => $roomPlayer->id,
                'action' => 'role.reassign',
                'outcome' => 'assigned',
                'details' => [
                    'role' => $role->slug,
                    'target_room_player_id' => $target->id,
                    'previous_holder_room_player_id' => $previousHolder?->room_player_id,
                ],
                'ip_hash' => $this->ipHash($request),
                'user_agent' => (string) $request->userAgent(),
                'created_at' => now(),
            ]);

            return $previousHolder !== null;
        });

        $message = $result
            ? 'Rola została przeniesiona. Poprzedni posiadacz nie ma teraz przypisanej roli.'
            : 'Rola została przypisana.';

        return redirect()->route('game')->with('success', $message);
    }

    public function storeCommand(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'direction' => ['required', 'string', 'in:N,NE,E,SE,S,SW,W,NW'],
            'ship_heading' => ['nullable', 'string', 'in:N,NE,E,SE,S,SW,W,NW'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:0', 'max:15'],
        ]);

        return response()->json([
            'status' => 'accepted',
            'message' => 'Polecenie kursu przyjęte do kolejki.',
            'payload' => $payload,
            'backend_contract' => 'not_implemented_yet',
        ], 202);
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }
}
