<?php

namespace App\Services\Game;

use App\Models\GameRoom;
use App\Models\GameRun;
use App\Models\GameSeries;
use App\Models\GameState;

class StartGameRun
{
    public function __construct(private readonly MapGenerator $mapGenerator) {}

    public function startForRoom(GameRoom $room): GameRun
    {
        $series = GameSeries::query()
            ->where('game_room_id', $room->id)
            ->where('status', 'in_progress')
            ->latest('series_number')
            ->first();

        if ($series !== null && $series->runs()->count() >= 3) {
            $series->update(['status' => 'completed', 'completed_at' => now()]);
            $series = null;
        }

        if ($series === null) {
            return $this->startFirst($room);
        }

        return $this->startNext($series);
    }

    public function startFirst(GameRoom $room): GameRun
    {
        $now = now();
        $seriesNumber = (int) GameSeries::query()
            ->where('game_room_id', $room->id)
            ->max('series_number') + 1;

        $series = GameSeries::create([
            'game_room_id' => $room->id,
            'series_number' => $seriesNumber,
            'status' => 'in_progress',
            'started_at' => $now,
        ]);

        return $this->startRunInSeries($series, 1);
    }

    public function startNext(GameSeries $series): GameRun
    {
        $nextRunNumber = (int) $series->runs()->max('run_number') + 1;

        if ($nextRunNumber > 3) {
            $series->update(['status' => 'completed', 'completed_at' => now()]);

            return $this->startFirst($series->gameRoom()->firstOrFail());
        }

        return $this->startRunInSeries($series, $nextRunNumber);
    }

    protected function startRunInSeries(GameSeries $series, int $runNumber): GameRun
    {
        $now = now();
        $seed = random_int(1, PHP_INT_MAX);
        $map = $this->mapGenerator->generate($seed);
        $run = $series->runs()->create([
            'run_number' => $runNumber,
            'status' => 'running',
            'map_seed' => $seed,
            'map_data' => $map,
            'started_at' => $now,
        ]);

        $run->state()->create([
            'position_x' => $map['start']['x'],
            'position_y' => $map['start']['y'],
            'heading' => 'SE',
            'tick_number' => 0,
            'moves_made' => 0,
            'next_tick_at' => $now->copy()->addSeconds(20),
        ]);

        return $run;
    }
}
