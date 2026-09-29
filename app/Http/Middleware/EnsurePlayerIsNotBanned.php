<?php

namespace App\Http\Middleware;

use App\Models\Player;
use App\Models\PlayerAction;
use App\Models\PlayerBan;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionToken = $request->session()->get('player_token');
        $player = $sessionToken
            ? Player::query()->where('session_key_hash', hash_hmac('sha256', $sessionToken, (string) config('app.key')))->first()
            : null;
        $ipHash = $request->ip()
            ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
            : null;

        if ($player === null && $ipHash === null) {
            return $next($request);
        }

        $ban = PlayerBan::query()->active()->where(function (Builder $query) use ($player, $ipHash) {
            if ($player !== null) {
                $query->where('player_id', $player->id);
            }

            if ($ipHash !== null) {
                $player !== null
                    ? $query->orWhere('ip_hash', $ipHash)
                    : $query->where('ip_hash', $ipHash);
            }
        })->first();

        if ($ban === null) {
            return $next($request);
        }

        PlayerAction::create([
            'player_id' => $player?->id,
            'action' => Str::limit((string) ($request->route()?->getName() ?? 'lobby.access'), 64, ''),
            'outcome' => 'banned',
            'details' => ['ban_id' => $ban->id],
            'ip_hash' => $ipHash,
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);

        return response('Dostęp do lobby został zablokowany.', Response::HTTP_FORBIDDEN);
    }
}
