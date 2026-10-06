<?php

namespace Tests\Feature;

use App\Models\GameRoom;
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
            ->assertSee('SE');

        $this->postJson(route('game.command.store'), [
            'direction' => 'SE',
            'cooldown_seconds' => 15,
            'ship_heading' => 'SE',
        ])
            ->assertStatus(202)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('payload.direction', 'SE');
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
