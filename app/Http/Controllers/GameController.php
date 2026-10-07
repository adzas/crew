<?php

namespace App\Http\Controllers;

use App\Models\GameCommand;
use App\Models\GameRoom;
use App\Models\GameRun;
use App\Models\PlayerAction;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use App\Services\Game\AdvanceDueGame;
use App\Services\Game\CourseRules;
use App\Services\Game\StartGameRun;
use App\Services\Lobby\RoomPlayerContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GameController extends Controller
{
    public function start(Request $request, RoomPlayerContext $context, StartGameRun $startGameRun): RedirectResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer && $roomPlayer->is_host, 403);

        return DB::transaction(function () use ($request, $roomPlayer, $startGameRun) {
            $room = GameRoom::query()
                ->whereKey($roomPlayer->game_room_id)
                ->lockForUpdate()
                ->firstOrFail();

            $activeRunExists = GameRun::query()
                ->whereHas('series', fn ($query) => $query->where('game_room_id', $room->id))
                ->where('status', 'running')
                ->exists();

            if ($room->status !== 'waiting' && ($room->status !== 'playing' || $activeRunExists)) {
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

            $run = $startGameRun->startForRoom($room);
            $room->update(['status' => 'playing', 'last_activity_at' => now()]);

            PlayerAction::create([
                'player_id' => $roomPlayer->player_id,
                'game_room_id' => $room->id,
                'room_player_id' => $roomPlayer->id,
                'action' => 'game.start',
                'outcome' => 'started',
                'details' => [
                    'game_series_id' => $run->game_series_id,
                    'game_run_id' => $run->id,
                    'run_number' => $run->run_number,
                    'captain' => $captainAssigned,
                    'helmsman' => $helmsmanAssigned,
                ],
                'ip_hash' => $this->ipHash($request),
                'user_agent' => (string) $request->userAgent(),
                'created_at' => now(),
            ]);

            return redirect()->route('game');
        });
    }

    public function index(Request $request, RoomPlayerContext $context): View
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer !== null, 403);

        $room = $roomPlayer->gameRoom()->with(['players.roleAssignment.role'])->firstOrFail();
        abort_unless($room->status === 'playing', 404);
        app(AdvanceDueGame::class)->advanceForRoom($room->id);

        $run = GameRun::query()
            ->whereHas('series', fn ($query) => $query->where('game_room_id', $room->id))
            ->with(['series.runs', 'state'])
            ->latest('id')
            ->firstOrFail();
        $state = $run->state;
        abort_unless($state !== null, 500);

        $effectiveRole = $context->effectiveRole($request, $roomPlayer);
        $isHelmsman = $effectiveRole?->slug === 'helmsman';
        $isCaptain = $effectiveRole?->slug === 'captain';
        $map = $run->map_data;
        $courseRules = app(CourseRules::class);

        return view('game', [
            'room' => $room,
            'roomPlayer' => $roomPlayer,
            'effectiveRole' => $effectiveRole,
            'run' => $run,
            'series' => $run->series,
            'shipPosition' => ['x' => $state->position_x, 'y' => $state->position_y],
            'target' => $map['target'],
            'obstacles' => $map['obstacles'],
            'mapSize' => $map['size'],
            'isHost' => $roomPlayer->is_host,
            'isHelmsman' => $isHelmsman,
            'isCaptain' => $isCaptain,
            'roles' => Role::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'selectedDirection' => $state->heading,
            'allowedDirections' => $courseRules->turnOptions($state->heading),
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

    public function storeCommand(Request $request, RoomPlayerContext $context, AdvanceDueGame $advanceDueGame): JsonResponse
    {
        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer !== null, 403);

        $role = $context->effectiveRole($request, $roomPlayer);
        abort_unless($role?->slug === 'helmsman', 403);

        $advanceDueGame->advanceForRoom($roomPlayer->game_room_id);
        $run = GameRun::query()
            ->whereHas('series', fn ($query) => $query->where('game_room_id', $roomPlayer->game_room_id))
            ->with('state')
            ->latest('id')
            ->first();

        if (! $run || $run->status !== 'running' || ! $run->state) {
            return response()->json(['status' => 'game_not_running'], 409);
        }

        $courseRules = app(CourseRules::class);
        $validated = $request->validate([
            'direction' => ['required', 'string', \Illuminate\Validation\Rule::in($courseRules->turnOptions($run->state->heading))],
        ]);
        $now = now();

        return DB::transaction(function () use ($roomPlayer, $role, $run, $validated, $now) {
            $state = $run->state()->lockForUpdate()->firstOrFail();
            $lockedRun = GameRun::query()->whereKey($run->id)->lockForUpdate()->firstOrFail();

            if ($lockedRun->status !== 'running') {
                return response()->json(['status' => 'game_not_running'], 409);
            }

            if ($state->last_command_at && $state->last_command_at->copy()->addSeconds(15)->gt($now)) {
                $retryAfter = $state->last_command_at->copy()->addSeconds(15)->timestamp - $now->timestamp;

                return response()->json([
                    'status' => 'cooldown',
                    'retry_after_seconds' => max(1, $retryAfter),
                ], 429);
            }

            $command = GameCommand::create([
                'game_run_id' => $lockedRun->id,
                'player_id' => $roomPlayer->player_id,
                'room_player_id' => $roomPlayer->id,
                'role_id' => $role->id,
                'command_type' => 'course',
                'payload' => ['direction' => $validated['direction']],
                'status' => 'pending',
                'submitted_at' => $now,
            ]);

            GameCommand::query()
                ->where('game_run_id', $lockedRun->id)
                ->where('command_type', 'course')
                ->where('status', 'pending')
                ->where('id', '!=', $command->id)
                ->update([
                    'status' => 'superseded',
                    'processed_at' => $now,
                    'superseded_by_command_id' => $command->id,
                ]);

            $state->last_command_at = $now;
            $state->save();

            return response()->json([
                'status' => 'accepted',
                'message' => 'Polecenie kursu przyjęte do kolejki.',
                'command_id' => $command->id,
                'payload' => $command->payload,
            ], 202);
        });
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }
}
