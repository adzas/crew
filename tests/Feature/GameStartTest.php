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
            ->assertSee('Mapa testowa');
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
}
