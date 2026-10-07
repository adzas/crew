<?php

namespace App\Services\Game;

use App\Models\GameCommand;
use App\Models\GameRun;
use App\Models\GameSeries;
use App\Models\GameTick;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AdvanceDueGame
{
    private const TICK_SECONDS = 20;

    public function __construct(private readonly CourseRules $courseRules) {}

    public function advanceForRoom(int $gameRoomId, ?CarbonInterface $now = null): void
    {
        $run = GameRun::query()
            ->where('status', 'running')
            ->whereHas('series', fn ($query) => $query->where('game_room_id', $gameRoomId))
            ->latest('id')
            ->first();

        if ($run) {
            $this->advance($run->id, $now);
        }
    }

    public function advance(int $gameRunId, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        DB::transaction(function () use ($gameRunId, $now) {
            $run = GameRun::query()->whereKey($gameRunId)->lockForUpdate()->firstOrFail();
            $state = $run->state()->lockForUpdate()->firstOrFail();

            while ($run->status === 'running' && $state->next_tick_at->lte($now)) {
                $tickAt = $state->next_tick_at->copy();
                $command = $run->commands()
                    ->where('status', 'pending')
                    ->where('submitted_at', '<=', $tickAt)
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id')
                    ->first();
                $headingBefore = $state->heading;
                $headingAfter = $command?->payload['direction'] ?? $headingBefore;
                [$offsetX, $offsetY] = $this->courseRules->offset($headingAfter);
                $attemptedX = $state->position_x + $offsetX;
                $attemptedY = $state->position_y + $offsetY;
                $map = $run->map_data;
                $obstacles = array_fill_keys(array_map(
                    fn (array $cell): string => $cell[0].':'.$cell[1],
                    $map['obstacles'],
                ), true);

                $reason = null;
                if ($attemptedX < 0 || $attemptedY < 0 || $attemptedX >= $map['size'] || $attemptedY >= $map['size']) {
                    $reason = 'boundary';
                } elseif (isset($obstacles[$attemptedX.':'.$attemptedY])) {
                    $reason = 'obstacle';
                }

                $reachedTarget = $reason === null
                    && $attemptedX === $map['target']['x']
                    && $attemptedY === $map['target']['y'];
                $outcome = $reason !== null ? 'lost' : ($reachedTarget ? 'won' : 'moved');
                $tickNumber = $state->tick_number + 1;

                GameTick::create([
                    'game_run_id' => $run->id,
                    'tick_number' => $tickNumber,
                    'from_x' => $state->position_x,
                    'from_y' => $state->position_y,
                    'attempted_x' => $attemptedX,
                    'attempted_y' => $attemptedY,
                    'heading_before' => $headingBefore,
                    'heading_after' => $headingAfter,
                    'outcome' => $outcome,
                    'end_reason' => $reason ?? ($reachedTarget ? 'target' : null),
                    'details' => ['applied_command_id' => $command?->id],
                    'processed_at' => $tickAt,
                ]);

                if ($command) {
                    $command->update(['status' => 'applied', 'processed_at' => $tickAt]);
                }

                $state->heading = $headingAfter;
                $state->tick_number = $tickNumber;
                $state->next_tick_at = $tickAt->copy()->addSeconds(self::TICK_SECONDS);
                if ($reason === null) {
                    $state->position_x = $attemptedX;
                    $state->position_y = $attemptedY;
                    $state->moves_made++;
                }
                $state->save();

                if ($outcome === 'won' || $outcome === 'lost') {
                    $run->update([
                        'status' => $outcome,
                        'outcome_reason' => $reason ?? 'target',
                        'finished_at' => $tickAt,
                    ]);

                    if ($run->run_number === 3) {
                        GameSeries::query()->whereKey($run->game_series_id)->update([
                            'status' => 'completed',
                            'completed_at' => $tickAt,
                        ]);
                    }
                }
            }
        });
    }
}
