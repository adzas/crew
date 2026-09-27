<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\Player;
use App\Models\PlayerAction;
use App\Models\PlayerJoinLog;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function index(Request $request): View
    {
        $roomPlayer = RoomPlayer::with(['gameRoom', 'roleAssignment.role'])
            ->find($request->session()->get('room_player_id'));

        if ($roomPlayer) {
            $roomPlayer->forceFill(['last_seen_at' => now()])->save();
            $roomPlayer->gameRoom->load(['players.roleAssignment.role']);
        }

        return view('lobby', [
            'roomPlayer' => $roomPlayer,
            'room' => $roomPlayer?->gameRoom,
            'roles' => Role::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function join(Request $request): RedirectResponse
    {
        $request->merge(['room_code' => $request->filled('room_code') ? strtoupper(trim($request->input('room_code'))) : null]);
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:24'],
            'room_code' => ['nullable', 'alpha_num', 'size:6'],
        ]);

        $sessionToken = $request->session()->get('player_token');
        if (! $sessionToken) {
            $sessionToken = Str::random(64);
            $request->session()->put('player_token', $sessionToken);
        }

        $ipHash = $this->ipHash($request);
        $userAgent = Str::limit((string) $request->userAgent(), 1000, '');

        $result = DB::transaction(function () use ($validated, $sessionToken, $ipHash, $userAgent) {
            $player = Player::firstOrCreate(
                ['session_key_hash' => hash_hmac('sha256', $sessionToken, (string) config('app.key'))],
                ['public_id' => (string) Str::uuid()],
            );

            $room = filled($validated['room_code'] ?? null)
                ? GameRoom::query()->where('code', $validated['room_code'])->where('status', 'waiting')->lockForUpdate()->first()
                : GameRoom::create(['code' => $this->newRoomCode(), 'status' => 'waiting', 'last_activity_at' => now()]);

            if (! $room) {
                $this->recordAction($player, null, null, 'room.join', 'rejected', ['reason' => 'room_not_found'], $ipHash, $userAgent);

                return ['error' => true];
            }

            $roomPlayer = RoomPlayer::firstOrCreate(
                ['game_room_id' => $room->id, 'player_id' => $player->id],
                [
                    'display_name' => $validated['display_name'],
                    'is_host' => $room->wasRecentlyCreated,
                    'joined_at' => now(),
                ],
            );

            if (! $roomPlayer->wasRecentlyCreated) {
                $roomPlayer->update(['display_name' => $validated['display_name'], 'last_seen_at' => now()]);
            }

            PlayerJoinLog::create([
                'player_id' => $player->id,
                'game_room_id' => $room->id,
                'room_player_id' => $roomPlayer->id,
                'ip_hash' => $ipHash,
                'user_agent' => $userAgent,
                'joined_at' => now(),
            ]);

            $room->update(['last_activity_at' => now()]);
            $this->recordAction(
                $player,
                $room,
                $roomPlayer,
                'room.join',
                $roomPlayer->wasRecentlyCreated ? 'joined' : 'returned',
                ['display_name' => $validated['display_name']],
                $ipHash,
                $userAgent,
            );

            return ['room_player_id' => $roomPlayer->id];
        });

        if ($result['error'] ?? false) {
            return back()->withInput()->withErrors(['room_code' => 'Nie znaleziono otwartego pokoju o podanym kodzie.']);
        }

        $request->session()->put('room_player_id', $result['room_player_id']);

        return redirect()->route('lobby');
    }

    public function claimRole(Request $request, string $role): RedirectResponse
    {
        $roomPlayer = RoomPlayer::with('gameRoom')->find($request->session()->get('room_player_id'));
        if (! $roomPlayer) {
            return redirect()->route('lobby')->withErrors(['lobby' => 'Dołącz do pokoju, aby wybrać rolę.']);
        }

        $ipHash = $this->ipHash($request);
        $userAgent = Str::limit((string) $request->userAgent(), 1000, '');
        $result = DB::transaction(function () use ($roomPlayer, $role, $ipHash, $userAgent) {
            $room = GameRoom::query()->whereKey($roomPlayer->game_room_id)->lockForUpdate()->first();
            $player = $roomPlayer->player()->first();
            $selectedRole = Role::query()->where('slug', $role)->first();

            if (! $selectedRole || ! $selectedRole->is_active) {
                $this->recordAction($player, $room, $roomPlayer, 'role.claim', 'rejected', ['role' => $role, 'reason' => 'inactive_or_unknown'], $ipHash, $userAgent);

                return ['error' => 'Ta rola nie jest obecnie dostępna.'];
            }

            $occupied = RoomPlayerRole::query()
                ->where('game_room_id', $room->id)
                ->where('role_id', $selectedRole->id)
                ->where('room_player_id', '!=', $roomPlayer->id)
                ->exists();

            if ($occupied) {
                $this->recordAction($player, $room, $roomPlayer, 'role.claim', 'rejected', ['role' => $role, 'reason' => 'occupied'], $ipHash, $userAgent);

                return ['error' => 'To stanowisko jest już zajęte.'];
            }

            RoomPlayerRole::updateOrCreate(
                ['game_room_id' => $room->id, 'room_player_id' => $roomPlayer->id],
                ['role_id' => $selectedRole->id, 'assigned_at' => now()],
            );

            $room->update(['last_activity_at' => now()]);
            $this->recordAction($player, $room, $roomPlayer, 'role.claim', 'claimed', ['role' => $role], $ipHash, $userAgent);

            return ['error' => null];
        });

        return redirect()->route('lobby')->with($result['error'] ? 'error' : 'success', $result['error'] ?? 'Rola została przypisana.');
    }

    public function logAction(Request $request, string $action): Response
    {
        abort_unless($action === 'invite.copy', 404);

        $roomPlayer = RoomPlayer::with('gameRoom', 'player')->find($request->session()->get('room_player_id'));
        abort_unless($roomPlayer !== null, 403);

        $roomPlayer->gameRoom->update(['last_activity_at' => now()]);
        $this->recordAction(
            $roomPlayer->player,
            $roomPlayer->gameRoom,
            $roomPlayer,
            $action,
            'recorded',
            [],
            $this->ipHash($request),
            Str::limit((string) $request->userAgent(), 1000, ''),
        );

        return response()->noContent();
    }

    private function newRoomCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GameRoom::where('code', $code)->exists());

        return $code;
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }

    private function recordAction(
        ?Player $player,
        ?GameRoom $room,
        ?RoomPlayer $roomPlayer,
        string $action,
        string $outcome,
        array $details,
        ?string $ipHash,
        string $userAgent,
    ): void {
        PlayerAction::create([
            'player_id' => $player?->id,
            'game_room_id' => $room?->id,
            'room_player_id' => $roomPlayer?->id,
            'action' => $action,
            'outcome' => $outcome,
            'details' => $details,
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
