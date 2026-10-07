<?php

namespace Tests\Unit;

use App\Models\GameRoom;
use App\Models\GameRun;
use App\Models\GameSeries;
use App\Models\GameState;
use App\Services\Game\AdvanceDueGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceDueGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_tick_moves_one_cell_and_reprocessing_the_same_time_is_idempotent(): void
    {
        [$run, $state] = $this->makeRun();
        $tickTime = now()->addSeconds(20);

        app(AdvanceDueGame::class)->advance($run->id, $tickTime);
        app(AdvanceDueGame::class)->advance($run->id, $tickTime);

        $state->refresh();
        $this->assertSame(5, $state->position_x);
        $this->assertSame(10, $state->position_y);
        $this->assertSame(1, $state->tick_number);
        $this->assertSame(1, $state->moves_made);
        $this->assertSame(1, $run->ticks()->count());
    }

    public function test_collision_ends_the_run_without_committing_an_illegal_position(): void
    {
        [$run, $state] = $this->makeRun([
            'position_x' => 19,
            'position_y' => 0,
            'heading' => 'N',
        ]);

        app(AdvanceDueGame::class)->advance($run->id, now()->addSeconds(20));

        $state->refresh();
        $run->refresh();
        $tick = $run->ticks()->firstOrFail();

        $this->assertSame('lost', $run->status);
        $this->assertSame('boundary', $run->outcome_reason);
        $this->assertSame(19, $state->position_x);
        $this->assertSame(0, $state->position_y);
        $this->assertSame(-1, $tick->attempted_y);
        $this->assertSame(0, $state->moves_made);
    }

    public function test_obstacle_collision_moves_the_ship_onto_the_obstacle_before_losing(): void
    {
        [$run, $state] = $this->makeRun([
            'position_x' => 4,
            'position_y' => 9,
            'heading' => 'E',
        ], [
            ['x' => 5, 'y' => 9],
        ]);

        app(AdvanceDueGame::class)->advance($run->id, now()->addSeconds(20));

        $state->refresh();
        $run->refresh();
        $tick = $run->ticks()->firstOrFail();

        $this->assertSame('lost', $run->status);
        $this->assertSame('obstacle', $run->outcome_reason);
        $this->assertSame(5, $state->position_x);
        $this->assertSame(9, $state->position_y);
        $this->assertSame(5, $tick->attempted_x);
        $this->assertSame(1, $state->moves_made);
    }

    private function makeRun(array $stateOverrides = [], array $obstacles = []): array
    {
        $room = GameRoom::create(['code' => strtoupper(fake()->unique()->lexify('??????')), 'status' => 'playing']);
        $series = GameSeries::create([
            'game_room_id' => $room->id,
            'series_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        $run = GameRun::create([
            'game_series_id' => $series->id,
            'run_number' => 1,
            'status' => 'running',
            'map_seed' => 123,
            'map_data' => [
                'size' => 20,
                'start' => ['x' => 4, 'y' => 9],
                'target' => ['x' => 16, 'y' => 15],
                'obstacles' => $obstacles,
            ],
            'started_at' => now(),
        ]);
        $state = GameState::create(array_merge([
            'game_run_id' => $run->id,
            'position_x' => 4,
            'position_y' => 9,
            'heading' => 'SE',
            'tick_number' => 0,
            'moves_made' => 0,
            'next_tick_at' => now()->addSeconds(20),
        ], $stateOverrides));

        return [$run, $state];
    }
}
