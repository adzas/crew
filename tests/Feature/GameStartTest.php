<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRun;
use App\Models\GameSeries;
use App\Models\GameState;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameStartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_host_can_start_the_game_when_captain_and_helmsman_are_assigned(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $hostSession = session()->all();
        $room = GameRoom::firstOrFail();
        $host = RoomPlayer::firstOrFail();

        $this->post(route('lobby.roles.claim', 'captain'));

        $this->withSession(['player_token' => str_repeat('g', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);

        $helmsman = RoomPlayer::query()->where('player_id', '!=', $host->player_id)->firstOrFail();
        RoomPlayerRole::create([
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => Role::where('slug', 'helmsman')->value('id'),
            'assigned_at' => now(),
        ]);

        $this->withSession($hostSession)
            ->post(route('game.start'))
            ->assertRedirect(route('game'));

        $this->assertDatabaseHas('game_rooms', ['id' => $room->id, 'status' => 'playing']);
        $series = GameSeries::query()->where('game_room_id', $room->id)->firstOrFail();
        $run = GameRun::query()->where('game_series_id', $series->id)->firstOrFail();
        $state = GameState::query()->where('game_run_id', $run->id)->firstOrFail();

        $this->assertSame(1, $series->series_number);
        $this->assertSame(1, $run->run_number);
        $this->assertSame('running', $run->status);
        $this->assertSame(20, $run->map_data['size']);
        $this->assertSame($run->map_data['start']['x'], $state->position_x);
        $this->assertSame($run->map_data['start']['y'], $state->position_y);
        $this->assertSame('SE', $state->heading);
        $this->assertSame(0, $state->tick_number);
        $this->assertSame(0, $state->moves_made);

        $this->get(route('game'))
            ->assertOk()
            ->assertSee('Ekran główny rozgrywki')
            ->assertSee('Mapa testowa')
            ->assertSee('Zarządzanie stanowiskami')
            ->assertDontSee('Panel sternika');

        $this->withSession([
            'player_token' => str_repeat('g', 64),
            'room_player_id' => $helmsman->id,
        ])
            ->getJson(route('game.status'))
            ->assertOk()
            ->assertJsonPath('status', 'playing');

        $this->withSession([
            'player_token' => str_repeat('g', 64),
            'room_player_id' => $helmsman->id,
        ])
            ->get(route('lobby'))
            ->assertRedirect(route('game'));
    }

    public function test_host_can_start_a_follow_up_run_in_the_same_series_after_a_finished_run(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $hostSession = session()->all();
        $room = GameRoom::firstOrFail();
        $host = RoomPlayer::firstOrFail();

        $this->post(route('lobby.roles.claim', 'captain'));

        $this->withSession(['player_token' => str_repeat('g', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);

        $helmsman = RoomPlayer::query()->where('player_id', '!=', $host->player_id)->firstOrFail();
        RoomPlayerRole::create([
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => Role::where('slug', 'helmsman')->value('id'),
            'assigned_at' => now(),
        ]);

        $this->withSession($hostSession)
            ->post(route('game.start'))
            ->assertRedirect(route('game'));

        $series = GameSeries::query()->where('game_room_id', $room->id)->firstOrFail();
        $run = GameRun::query()->where('game_series_id', $series->id)->latest('id')->firstOrFail();
        $run->update(['status' => 'lost', 'outcome_reason' => 'boundary']);
        $run->state()->update([
            'position_x' => 19,
            'position_y' => 0,
            'heading' => 'N',
            'next_tick_at' => now()->addSeconds(20),
        ]);

        $this->withSession($hostSession)
            ->post(route('game.start'))
            ->assertRedirect(route('game'));

        $this->assertDatabaseHas('game_runs', [
            'game_series_id' => $series->id,
            'run_number' => 2,
            'status' => 'running',
        ]);
        $this->assertSame('in_progress', $series->fresh()->status);
    }

    public function test_host_cannot_start_the_game_without_capitan_and_helmsman_roles(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $this->post(route('lobby.roles.claim', 'captain'));

        $this->post(route('game.start'))
            ->assertRedirect(route('lobby'))
            ->assertSessionHas('error', 'Aby uruchomić rozgrywkę, kapitan i sternik muszą być już obsadzeni.');
    }

    public function test_non_host_cannot_start_the_game(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $room = GameRoom::firstOrFail();
        $host = RoomPlayer::firstOrFail();

        $this->post(route('lobby.roles.claim', 'captain'));

        $this->withSession(['player_token' => str_repeat('h', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);

        $this->withSession(['room_player_id' => RoomPlayer::query()->where('player_id', '!=', $host->player_id)->value('id')])
            ->post(route('game.start'))
            ->assertForbidden();
    }

    public function test_helmsman_panel_accepts_a_direction_payload_for_backend_contract(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $hostToken = session('player_token');
        $hostRoomPlayerId = session('room_player_id');
        $room = GameRoom::firstOrFail();
        $host = RoomPlayer::firstOrFail();

        $this->post(route('lobby.roles.claim', 'captain'));

        $this->withSession(['player_token' => str_repeat('j', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);

        $helmsman = RoomPlayer::query()->where('player_id', '!=', $host->player_id)->firstOrFail();
        RoomPlayerRole::create([
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => Role::where('slug', 'helmsman')->value('id'),
            'assigned_at' => now(),
        ]);

        $this->withSession([
            'player_token' => $hostToken,
            'room_player_id' => $hostRoomPlayerId,
        ])
            ->post(route('game.start'))
            ->assertRedirect(route('game'));

        $this->withSession([
            'player_token' => str_repeat('j', 64),
            'room_player_id' => $helmsman->id,
        ])
            ->get(route('game'))
            ->assertOk()
            ->assertSee('Panel sternika')
            ->assertSee('Ustaw kierunek')
            ->assertDontSee('Mapa testowa')
            ->assertDontSee('Zarządzanie stanowiskami')
            ->assertSee('SE')
            ->assertSee('window.setInterval')
            ->assertSee('--ship-angle');

        $this->postJson(route('game.command.store'), [
            'direction' => 'E',
            'cooldown_seconds' => 0,
            'ship_heading' => 'NW',
        ])
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('payload.direction', 'E');

        $firstCommand = \App\Models\GameCommand::query()->firstOrFail();
        $this->assertSame('pending', $firstCommand->status);

        $this->travel(14)->seconds();
        $this->postJson(route('game.command.store'), ['direction' => 'S'])
            ->assertStatus(429);

        $this->travel(1)->seconds();
        $this->postJson(route('game.command.store'), ['direction' => 'S'])
            ->assertStatus(202)
            ->assertJsonPath('payload.direction', 'S');

        $this->travel(5)->seconds();
        $this->get(route('game'))->assertOk();

        $firstCommand->refresh();
        $state = \App\Models\GameState::query()->where('game_run_id', $firstCommand->game_run_id)->firstOrFail();
        $this->assertSame('superseded', $firstCommand->status);
        $this->assertSame('applied', \App\Models\GameCommand::query()->where('id', '!=', $firstCommand->id)->value('status'));
        $this->assertSame(4, $state->position_x);
        $this->assertSame(10, $state->position_y);
        $this->assertSame('S', $state->heading);
        $this->assertSame(1, $state->tick_number);
    }

    public function test_host_can_reassign_roles_even_after_changing_from_captain_to_helmsman(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $captainToken = session('player_token');
        $captainRoomPlayerId = session('room_player_id');
        $room = GameRoom::firstOrFail();
        $captain = RoomPlayer::firstOrFail();
        $captainRoleId = Role::where('slug', 'captain')->value('id');

        $this->post(route('lobby.roles.claim', 'captain'));
        $this->withSession(['player_token' => str_repeat('k', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);
        $helmsman = RoomPlayer::query()->where('player_id', '!=', $captain->player_id)->firstOrFail();
        $helmsmanRoleId = Role::where('slug', 'helmsman')->value('id');
        RoomPlayerRole::create([
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => $helmsmanRoleId,
            'assigned_at' => now(),
        ]);

        $this->withSession([
            'player_token' => $captainToken,
            'room_player_id' => $captainRoomPlayerId,
        ])->post(route('game.start'));

        $this->withSession([
            'player_token' => $captainToken,
            'room_player_id' => $captainRoomPlayerId,
        ])
            ->post(route('game.roles.assign', $captain), [
                'role' => 'helmsman',
            ])
            ->assertRedirect(route('game'));

        $this->assertDatabaseHas('room_player_roles', [
            'game_room_id' => $room->id,
            'room_player_id' => $captain->id,
            'role_id' => $helmsmanRoleId,
        ]);
        $this->assertDatabaseMissing('room_player_roles', [
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => $helmsmanRoleId,
        ]);
        $this->assertDatabaseMissing('room_player_roles', [
            'game_room_id' => $room->id,
            'room_player_id' => $captain->id,
            'role_id' => $captainRoleId,
        ]);

        $this->withSession([
            'player_token' => $captainToken,
            'room_player_id' => $captainRoomPlayerId,
        ])
            ->get(route('game'))
            ->assertOk()
            ->assertSeeText('Rola: Sternik')
            ->assertSee('Panel sternika')
            ->assertSee('Zarządzanie stanowiskami')
            ->assertDontSee('Mapa testowa');
    }

    public function test_non_captain_cannot_reassign_roles_during_the_game(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $hostToken = session('player_token');
        $hostRoomPlayerId = session('room_player_id');
        $room = GameRoom::firstOrFail();
        $host = RoomPlayer::firstOrFail();
        $this->post(route('lobby.roles.claim', 'captain'));

        $this->withSession(['player_token' => str_repeat('m', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $room->code]);
        $helmsman = RoomPlayer::query()->where('player_id', '!=', $host->player_id)->firstOrFail();
        RoomPlayerRole::create([
            'game_room_id' => $room->id,
            'room_player_id' => $helmsman->id,
            'role_id' => Role::where('slug', 'helmsman')->value('id'),
            'assigned_at' => now(),
        ]);

        $this->withSession([
            'player_token' => $hostToken,
            'room_player_id' => $hostRoomPlayerId,
        ])->post(route('game.start'));

        $this->withSession([
            'player_token' => str_repeat('m', 64),
            'room_player_id' => $helmsman->id,
        ])
            ->post(route('game.roles.assign', $helmsman), [
                'role' => 'captain',
            ])
            ->assertForbidden();
    }
}
