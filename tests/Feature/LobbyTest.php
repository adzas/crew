<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\Player;
use App\Models\PlayerAction;
use App\Models\PlayerBan;
use App\Models\PlayerJoinLog;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Models\RoomPlayerRole;
use App\Services\Lobby\RoomNameGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LobbyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_first_player_creates_a_room_and_can_claim_a_role(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala'])
            ->assertRedirect(route('lobby'));

        $room = GameRoom::firstOrFail();
        $roomPlayer = RoomPlayer::firstOrFail();

        $this->assertSame(6, strlen($room->code));
        $this->assertContains($room->name, RoomNameGenerator::NAMES);
        $this->assertTrue($roomPlayer->is_host);
        $this->assertDatabaseHas('player_join_logs', ['room_player_id' => $roomPlayer->id]);

        $this->post(route('lobby.roles.claim', 'captain'))
            ->assertRedirect(route('lobby'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('room_player_roles', [
            'game_room_id' => $room->id,
            'room_player_id' => $roomPlayer->id,
        ]);

        $this->get(route('lobby'))
            ->assertOk()
            ->assertSee($room->name)
            ->assertSee('Kapitan')
            ->assertSee('Ala');
    }

    public function test_role_can_only_be_claimed_once_per_room_and_rejections_are_logged(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $roomCode = GameRoom::firstOrFail()->code;
        $captain = session('room_player_id');

        $this->withSession(['player_token' => str_repeat('b', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $roomCode]);
        $helmsman = session('room_player_id');

        RoomPlayerRole::create([
            'game_room_id' => GameRoom::firstOrFail()->id,
            'room_player_id' => $captain,
            'role_id' => Role::where('slug', 'captain')->value('id'),
            'assigned_at' => now(),
        ]);

        $this->withSession(['room_player_id' => $helmsman])
            ->post(route('lobby.roles.claim', 'captain'))
            ->assertSessionHas('error', 'To stanowisko jest już zajęte.');

        $this->assertSame(1, RoomPlayerRole::where('role_id', Role::where('slug', 'captain')->value('id'))->count());
        $this->assertDatabaseHas('player_actions', ['action' => 'role.claim', 'outcome' => 'rejected']);
    }

    public function test_inactive_role_cannot_be_claimed(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);

        $this->post(route('lobby.roles.claim', 'gunner'))
            ->assertSessionHas('error', 'Ta rola nie jest obecnie dostępna.');

        $this->assertSame(0, RoomPlayerRole::count());
    }

    public function test_room_code_join_adds_a_second_player_without_creating_another_room(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $roomCode = GameRoom::firstOrFail()->code;

        $this->withSession(['player_token' => str_repeat('c', 64)])
            ->post(route('lobby.join'), ['display_name' => 'Jan', 'room_code' => $roomCode]);

        $this->assertSame(1, GameRoom::count());
        $this->assertSame(2, RoomPlayer::count());
        $this->assertSame(2, PlayerJoinLog::count());
        $this->assertSame(2, PlayerAction::where('action', 'room.join')->count());
    }

    public function test_rejoining_the_same_room_keeps_one_membership_and_records_each_join(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);
        $roomCode = GameRoom::firstOrFail()->code;

        $this->post(route('lobby.join'), ['display_name' => 'Ala', 'room_code' => $roomCode]);

        $this->assertSame(1, GameRoom::count());
        $this->assertSame(1, RoomPlayer::count());
        $this->assertSame(2, PlayerJoinLog::count());
    }

    public function test_copying_an_invite_is_logged_as_a_player_action(): void
    {
        $this->post(route('lobby.join'), ['display_name' => 'Ala']);

        $this->post(route('lobby.actions', 'invite.copy'))
            ->assertNoContent();

        $this->assertDatabaseHas('player_actions', [
            'action' => 'invite.copy',
            'outcome' => 'recorded',
        ]);
    }

    public function test_active_ip_ban_blocks_lobby_access_and_logs_the_attempt(): void
    {
        PlayerBan::create([
            'ip_hash' => hash_hmac('sha256', '127.0.0.1', (string) config('app.key')),
            'reason' => 'Spam',
        ]);

        $this->post(route('lobby.join'), ['display_name' => 'Ala'])
            ->assertForbidden();

        $this->assertSame(0, GameRoom::count());
        $this->assertDatabaseHas('player_actions', [
            'action' => 'lobby.join',
            'outcome' => 'banned',
        ]);
    }

    public function test_active_player_ban_blocks_even_from_a_different_ip(): void
    {
        $token = str_repeat('d', 64);
        $player = Player::create([
            'public_id' => (string) Str::uuid(),
            'session_key_hash' => hash_hmac('sha256', $token, (string) config('app.key')),
        ]);
        PlayerBan::create(['player_id' => $player->id, 'reason' => 'Abuse']);

        $this->withSession(['player_token' => $token])
            ->get(route('lobby'))
            ->assertForbidden();
    }

    public function test_expired_ban_does_not_block_lobby_access(): void
    {
        PlayerBan::create([
            'ip_hash' => hash_hmac('sha256', '127.0.0.1', (string) config('app.key')),
            'reason' => 'Temporary spam',
            'expires_at' => now()->subMinute(),
        ]);

        $this->get(route('lobby'))->assertOk();
    }
}
