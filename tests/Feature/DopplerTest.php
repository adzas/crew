<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DopplerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_host_can_simulate_an_active_role_without_changing_the_roster(): void
    {
        config(['game.doppler.enabled' => true]);

        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $host = RoomPlayer::with('roleAssignment')->firstOrFail();
        $room = GameRoom::firstOrFail();
        $captainRole = Role::where('slug', 'captain')->firstOrFail();
        $helmsmanRole = Role::where('slug', 'helmsman')->firstOrFail();

        $this->post(route('lobby.roles.claim', 'captain'));

        $this->post(route('lobby.doppler.switch'), ['role' => 'helmsman'])
            ->assertRedirect(route('lobby'))
            ->assertSessionHas('success', 'Tryb Doppler: działasz jako Sternik.');

        $this->assertSame($helmsmanRole->id, session('doppler_role_id'));
        $this->assertSame(1, RoomPlayerRole::count());
        $this->assertDatabaseHas('room_player_roles', [
            'game_room_id' => $room->id,
            'room_player_id' => $host->id,
            'role_id' => $captainRole->id,
        ]);
        $this->assertDatabaseHas('player_actions', [
            'player_id' => $host->player_id,
            'room_player_id' => $host->id,
            'acting_as_role_id' => $helmsmanRole->id,
            'action' => 'doppler.switch',
            'outcome' => 'recorded',
        ]);

        $this->get(route('lobby'))
            ->assertOk()
            ->assertSee('TRYB DOPPLER AKTYWNY')
            ->assertSee('działasz jako Sternik');

        $this->post(route('lobby.doppler.stop'))
            ->assertRedirect(route('lobby'))
            ->assertSessionMissing('doppler_role_id');

        $this->assertDatabaseHas('player_actions', [
            'player_id' => $host->player_id,
            'acting_as_role_id' => $helmsmanRole->id,
            'action' => 'doppler.stop',
        ]);
        $this->assertSame(1, RoomPlayerRole::count());
    }

    public function test_doppler_is_disabled_by_default(): void
    {
        config(['game.doppler.enabled' => false]);
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);

        $this->post(route('lobby.doppler.switch'), ['role' => 'captain'])
            ->assertNotFound();
    }

    public function test_doppler_is_unavailable_in_production_even_when_enabled(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        config(['app.env' => 'production', 'game.doppler.enabled' => true]);

        $this->post(route('lobby.doppler.switch'), ['role' => 'captain'])
            ->assertNotFound();

        $this->assertNull(session('doppler_role_id'));
    }

    public function test_non_host_cannot_switch_the_doppler_role(): void
    {
        config(['game.doppler.enabled' => true]);
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $roomCode = GameRoom::firstOrFail()->code;

        $this->withSession(['player_token' => str_repeat('e', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $roomCode]);

        $this->post(route('lobby.doppler.switch'), ['role' => 'captain'])
            ->assertForbidden();
    }

    public function test_session_cannot_impersonate_a_room_member_belonging_to_another_player(): void
    {
        config(['game.doppler.enabled' => true]);
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $hostId = session('room_player_id');
        $roomCode = GameRoom::firstOrFail()->code;

        $this->withSession(['player_token' => str_repeat('f', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $roomCode]);

        $this->withSession(['room_player_id' => $hostId])
            ->post(route('lobby.doppler.switch'), ['role' => 'captain'])
            ->assertForbidden();
    }

    public function test_inactive_role_cannot_be_simulated(): void
    {
        config(['game.doppler.enabled' => true]);
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);

        $this->post(route('lobby.doppler.switch'), ['role' => 'gunner'])
            ->assertSessionHasErrors('role');

        $this->assertNull(session('doppler_role_id'));
    }
}
