<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rozgrywka | Crew</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #091719;
            --panel: #102a2b;
            --panel-alt: #17393c;
            --line: #2a4d52;
            --ink: #e8f3f2;
            --muted: #9ec1bf;
            --sea: #123a3e;
            --ship: #d9b76a;
            --goal: #8ddba0;
            --wall: #4d6467;
            --danger: #ef6a48;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background: linear-gradient(180deg, #0a1a1d 0%, #0f2327 100%);
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .shell {
            width: min(1200px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 48px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            padding: 18px 20px;
            background: rgba(14, 36, 38, 0.9);
            border: 1px solid var(--line);
            border-radius: 14px;
        }
        .eyebrow {
            margin: 0;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-size: 11px;
            font-weight: 700;
        }
        .title {
            margin: 6px 0 0;
            font-size: clamp(28px, 5vw, 46px);
            line-height: 1.0;
            font-weight: 700;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(141, 219, 160, 0.12);
            border: 1px solid rgba(141, 219, 160, 0.5);
            color: #dff8e4;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.8fr);
            gap: 24px;
            align-items: start;
        }
        .panel {
            background: rgba(20, 45, 46, 0.88);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.15);
        }
        .map-panel { padding: 18px; }
        .map {
            display: grid;
            grid-template-columns: repeat({{ $mapSize }}, minmax(0, 1fr));
            gap: 2px;
            width: min(100%, 620px);
            max-width: 100%;
            aspect-ratio: 1;
            margin: 14px auto 0;
            padding: 10px;
            background: rgba(9, 23, 25, 0.7);
            border: 1px solid var(--line);
            border-radius: 12px;
        }
        .tile {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 16px;
            border-radius: 3px;
            background: var(--sea);
            position: relative;
            overflow: hidden;
        }
        .tile.wall { background: var(--wall); }
        .tile.goal { background: rgba(141, 219, 160, 0.25); border: 1px solid rgba(141, 219, 160, 0.8); }
        .tile.ship { background: linear-gradient(135deg, rgba(217,183,106,0.95), rgba(180,142,68,.9)); }
        .tile.ship::after {
            content: '◢';
            font-size: 14px;
            color: #1a1a1a;
        }
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 14px;
            color: var(--muted);
            font-size: 12px;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            display: inline-block;
        }
        .sidebar {
            display: grid;
            gap: 18px;
            padding: 18px;
        }
        .meta {
            display: grid;
            gap: 10px;
            color: var(--muted);
            font-size: 13px;
        }
        .meta strong { color: var(--ink); }
        .actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        button {
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .primary {
            background: #d9b76a;
            color: #122124;
        }
        .secondary {
            background: rgba(158, 193, 191, 0.12);
            color: var(--ink);
            border: 1px solid var(--line);
        }
        .player-list, .role-list {
            width: 100%;
            border-collapse: collapse;
            color: var(--ink);
        }
        .player-list th, .player-list td, .role-list th, .role-list td {
            padding: 8px 0;
            border-bottom: 1px solid var(--line);
            text-align: left;
            font-size: 13px;
        }
        .role-tag {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(217,183,106,0.12);
            border: 1px solid rgba(217,183,106,0.5);
            color: #f5ddb3;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        @media (max-width: 840px) {
            .grid { grid-template-columns: 1fr; }
            .shell { width: min(100% - 20px, 1200px); }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Crew / rozgrywka</p>
                <h1 class="title">Ekran główny rozgrywki</h1>
            </div>
            <div class="status-pill">Status: {{ strtoupper($room->status) }}</div>
        </header>

        <div class="grid">
            <section class="panel map-panel" aria-labelledby="map-heading">
                <h2 id="map-heading" style="margin:0; font-size: 24px;">Mapa testowa</h2>
                <div class="map" aria-label="Mapa rozgrywki">
                    @for ($y = 0; $y < $mapSize; $y++)
                        @for ($x = 0; $x < $mapSize; $x++)
                            @php
                                $isWall = collect($obstacles)->contains(fn ($cell) => $cell[0] === $x && $cell[1] === $y);
                                $isShip = $shipPosition['x'] === $x && $shipPosition['y'] === $y;
                                $isGoal = $target['x'] === $x && $target['y'] === $y;
                            @endphp
                            <div class="tile {{ $isWall ? 'wall' : '' }} {{ $isGoal ? 'goal' : '' }} {{ $isShip ? 'ship' : '' }}" aria-label="Pole {{ $x + 1 }}, {{ $y + 1 }}"></div>
                        @endfor
                    @endfor
                </div>
                <div class="legend">
                    <span class="legend-item"><span class="dot" style="background: var(--sea)"></span> Woda</span>
                    <span class="legend-item"><span class="dot" style="background: var(--ship)"></span> Statek</span>
                    <span class="legend-item"><span class="dot" style="background: var(--goal)"></span> Cel</span>
                    <span class="legend-item"><span class="dot" style="background: var(--wall)"></span> Przeszkoda</span>
                </div>
            </section>

            <aside class="panel sidebar" aria-label="Panel rozgrywki">
                <div>
                    <p class="eyebrow">Dane pokoju</p>
                    <div class="meta" style="margin-top: 12px;">
                        <div><strong>Kod:</strong> {{ $room->code }}</div>
                        <div><strong>Gospodarz:</strong> {{ $room->players->first(fn ($player) => $player->is_host)?->display_name ?? '—' }}</div>
                        <div><strong>Rola:</strong> {{ $effectiveRole?->name ?? 'Gracz' }}</div>
                    </div>
                </div>

                <div>
                    <p class="eyebrow">Akcje</p>
                    <div class="actions" style="margin-top: 12px;">
                        <button class="primary" type="button">Zapisz kurs</button>
                        <button class="secondary" type="button">Przywróć widok</button>
                    </div>
                </div>

                <div>
                    <p class="eyebrow">Załoga</p>
                    <table class="player-list" style="margin-top: 12px;">
                        <thead>
                            <tr>
                                <th>Gracz</th>
                                <th>Rola</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($room->players as $member)
                                <tr>
                                    <td>{{ $member->display_name }} @if($member->is_host) <span class="role-tag">Host</span> @endif</td>
                                    <td>{{ $member->roleAssignment?->role?->name ?? 'Brak' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
