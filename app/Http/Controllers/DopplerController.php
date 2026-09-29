<?php

namespace App\Http\Controllers;

use App\Models\PlayerAction;
use App\Models\Role;
use App\Models\RoomPlayer;
use App\Services\Lobby\RoomPlayerContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DopplerController extends Controller
{
    public function switchRole(Request $request, RoomPlayerContext $context): RedirectResponse
    {
        abort_unless($context->dopplerEnabled(), 404);

        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer !== null && $roomPlayer->is_host, 403);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'slug')->where('is_active', true)],
        ]);
        $role = Role::query()->where('slug', $validated['role'])->where('is_active', true)->firstOrFail();
        $request->session()->put('doppler_role_id', $role->id);
        $request->session()->put('doppler_room_player_id', $roomPlayer->id);

        $this->recordAction($request, $roomPlayer, 'doppler.switch', $role);

        return redirect()->route('lobby')->with('success', 'Tryb Doppler: działasz jako '.$role->name.'.');
    }

    public function stop(Request $request, RoomPlayerContext $context): RedirectResponse
    {
        abort_unless($context->dopplerEnabled(), 404);

        $roomPlayer = $context->current($request);
        abort_unless($roomPlayer !== null && $roomPlayer->is_host, 403);

        $role = $context->dopplerRole($request, $roomPlayer);
        $request->session()->forget(['doppler_role_id', 'doppler_room_player_id']);

        if ($role) {
            $this->recordAction($request, $roomPlayer, 'doppler.stop', $role);
        }

        return redirect()->route('lobby')->with('success', 'Wrócono do własnej roli.');
    }

    private function recordAction(Request $request, RoomPlayer $roomPlayer, string $action, Role $role): void
    {
        PlayerAction::create([
            'player_id' => $roomPlayer->player_id,
            'game_room_id' => $roomPlayer->game_room_id,
            'room_player_id' => $roomPlayer->id,
            'acting_as_role_id' => $role->id,
            'action' => $action,
            'outcome' => 'recorded',
            'details' => ['role' => $role->slug],
            'ip_hash' => $request->ip()
                ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
                : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }
}
