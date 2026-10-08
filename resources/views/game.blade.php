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
        .grid.single-panel { grid-template-columns: minmax(0, 1fr); }
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
        .local-map {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 3px;
            width: min(100%, 280px);
            aspect-ratio: 1;
            margin: 14px 0;
            padding: 8px;
            background: rgba(9, 23, 25, 0.7);
            border: 1px solid var(--line);
            border-radius: 8px;
        }
        .local-map .tile {
            min-height: 0;
            aspect-ratio: 1;
        }
        .local-map .tile.outside {
            background: transparent;
            border: 1px dashed rgba(158, 193, 191, 0.22);
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
        .tile.ship {
            background: transparent;
        }
        .tile.ship::before,
        .tile.ship::after {
            content: '';
            position: absolute;
            left: 14%;
            top: 43%;
            width: 72%;
            height: 14%;
            border-radius: 2px;
            background: var(--ship);
            box-shadow: 0 0 0 1px rgba(9, 23, 25, 0.9), 0 0 7px rgba(255, 220, 125, 0.6);
        }
        .tile.ship::before {
            transform: rotate(45deg);
        }
        .tile.ship::after {
            transform: rotate(-45deg);
        }
        .tile.ship.crash {
            background: linear-gradient(135deg, rgba(181, 52, 41, 0.96), rgba(108, 25, 21, 0.9));
        }
        .tile.ship.crash::before,
        .tile.ship.crash::after {
            background: #ff9278;
            box-shadow: 0 0 0 1px rgba(45, 12, 12, 0.9), 0 0 8px rgba(255, 110, 82, 0.8);
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
        .helmsman-panel {
            margin-top: 18px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(9, 23, 25, 0.82);
        }
        .helmsman-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .helmsman-header h3 {
            margin: 6px 0 0;
            font-size: 20px;
        }
        .cooldown-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 58px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(217, 183, 106, 0.12);
            border: 1px solid rgba(217, 183, 106, 0.5);
            color: #f3dc9c;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .compass {
            display: grid;
            grid-template-columns: repeat(3, minmax(54px, 1fr));
            gap: 8px;
            margin: 18px 0 14px;
        }
        .dir-btn {
            min-height: 54px;
            border: 1px solid rgba(158, 193, 191, 0.35);
            background: rgba(18, 58, 62, 0.85);
            color: var(--ink);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.05em;
            transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .dir-btn.disabled {
            opacity: 0.45;
            cursor: not-allowed;
            color: rgba(224, 232, 232, 0.5);
            background: rgba(109, 129, 130, 0.2);
            border-color: rgba(158, 193, 191, 0.15);
        }
        .dir-btn.center-rose {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 54px;
            border-radius: 10px;
            background: rgba(12, 34, 35, 0.85);
            border: 1px solid rgba(158, 193, 191, 0.18);
            color: rgba(228, 235, 228, 0.7);
            font-size: 20px;
            pointer-events: none;
        }
        .dir-btn:hover { transform: translateY(-1px); }
        .dir-btn.active {
            background: linear-gradient(135deg, rgba(217,183,106,0.38), rgba(217,183,106,0.18));
            border-color: rgba(217,183,106,0.9);
            box-shadow: 0 0 0 1px rgba(217,183,106,0.5), 0 8px 18px rgba(217,183,106,0.18);
            color: #fbefcb;
        }
        .helmsman-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(12, 34, 35, 0.85);
            border: 1px solid var(--line);
        }
        .helmsman-summary span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .helmsman-summary strong {
            display: block;
            margin-top: 4px;
            font-size: 18px;
            color: var(--ink);
        }
        .helmsman-status {
            min-height: 22px;
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }
        .helmsman-status.ok {
            color: #d8f7dd;
        }
        .helmsman-status.error {
            color: #ffc7ad;
        }
        .role-management {
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(9, 23, 25, 0.62);
        }
        .role-management h3 {
            margin: 6px 0 14px;
            font-size: 18px;
        }
        .role-assignment {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid var(--line);
        }
        .role-assignment label {
            grid-column: 1 / -1;
            color: var(--muted);
            font-size: 12px;
        }
        .role-assignment select {
            min-width: 0;
            min-height: 40px;
            padding: 0 8px;
            color: var(--ink);
            background: var(--panel-alt);
            border: 1px solid var(--line);
            border-radius: 6px;
            font: inherit;
        }
        .role-assignment button {
            min-height: 40px;
            padding: 8px 10px;
            border-radius: 6px;
        }
        .role-feedback {
            margin: 0 0 12px;
            color: #d8f7dd;
            font-size: 12px;
            line-height: 1.5;
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
    <div class="shell" data-game-running="{{ $run->status === 'running' ? 'true' : 'false' }}">
        <span hidden data-refresh-countdown data-next-tick-at="{{ $nextTickAt?->toIso8601String() }}"></span>
        <header class="topbar">
            <div>
                <p class="eyebrow">Crew / rozgrywka</p>
                <h1 class="title">Ekran główny rozgrywki</h1>
            </div>
            <div class="status-pill">Status: {{ strtoupper($room->status) }}</div>
        </header>

        <div class="grid {{ ($isCaptain ?? false) ? '' : 'single-panel' }}">
            @if (($isCaptain ?? false))
                <section class="panel map-panel" aria-labelledby="map-heading">
                    <h2 id="map-heading" style="margin:0; font-size: 24px;">Mapa</h2>
                    <p class="helmsman-status" style="margin-top: 8px;">
                        Podgląd odświeży się po ruchu statku · Czas do następnego ruchu: <strong data-decision-countdown data-next-tick-at="{{ $nextTickAt?->toIso8601String() }}">—</strong>
                    </p>
                    <div class="map" aria-label="Mapa rozgrywki">
                        @for ($y = 0; $y < $mapSize; $y++)
                            @for ($x = 0; $x < $mapSize; $x++)
                                @php
                                    $isWall = collect($obstacles)->contains(function ($cell) use ($x, $y) {
                                        if (is_array($cell) && array_key_exists('x', $cell) && array_key_exists('y', $cell)) {
                                            return (int) $cell['x'] === $x && (int) $cell['y'] === $y;
                                        }

                                        return is_array($cell) && isset($cell[0], $cell[1]) && (int) $cell[0] === $x && (int) $cell[1] === $y;
                                    });
                                    $isShip = $shipPosition['x'] === $x && $shipPosition['y'] === $y;
                                    $isGoal = $target['x'] === $x && $target['y'] === $y;
                                    $isCrash = $isShip && $isWall && ($run->outcome_reason ?? null) === 'obstacle';
                                @endphp
                                <div class="tile {{ $isWall ? 'wall' : '' }} {{ $isGoal ? 'goal' : '' }} {{ $isShip ? 'ship' : '' }} {{ $isCrash ? 'crash' : '' }}" aria-label="Pole {{ $x + 1 }}, {{ $y + 1 }}" @if ($isShip) data-ship-marker="x" @endif></div>
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
            @endif

            <aside class="panel sidebar" aria-label="Panel rozgrywki">
                <div>
                    <p class="eyebrow">Dane pokoju</p>
                    <div class="meta" style="margin-top: 12px;">
                        <div><strong>Kod:</strong> {{ $room->code }}</div>
                        <div><strong>Gospodarz:</strong> {{ $room->players->first(fn ($player) => $player->is_host)?->display_name ?? '—' }}</div>
                        <div><strong>Rola:</strong> {{ $effectiveRole?->name ?? 'Gracz' }}</div>
                    </div>
                </div>

                @if (($run->status ?? 'running') !== 'running')
                    <div class="helmsman-panel">
                        <p class="eyebrow">Podsumowanie partii</p>
                        <h3 style="margin: 6px 0 12px; font-size: 22px;">{{ $run->status === 'won' ? 'Zwycięstwo' : 'Porażka' }}</h3>

                        <div class="helmsman-summary">
                            <div>
                                <span>Wynik</span>
                                <strong>{{ $run->status === 'won' ? 'Cel osiągnięty' : 'Statek zniszczony' }}</strong>
                            </div>
                            <div>
                                <span>Ruchy</span>
                                <strong>{{ $run->state?->moves_made ?? 0 }}</strong>
                            </div>
                        </div>

                        @if (($isHost ?? false))
                            <form method="POST" action="{{ route('game.start') }}">
                                @csrf
                                <button class="primary" type="submit" style="width:100%; margin-top: 10px;">Rozpocznij kolejną partię</button>
                            </form>
                        @endif
                    </div>
                @elseif (($isHelmsman ?? false))
                    <section class="helmsman-panel" aria-labelledby="local-map-heading">
                        <p class="eyebrow">Otoczenie</p>
                        <h3 id="local-map-heading" style="margin: 6px 0 0; font-size: 18px;">Okolica statku · 5 × 5</h3>
                        <div class="local-map" data-local-map-size="5" aria-label="Pięć na pięć pól wokół statku">
                            @for ($localY = -2; $localY <= 2; $localY++)
                                @for ($localX = -2; $localX <= 2; $localX++)
                                    @php
                                        $x = $shipPosition['x'] + $localX;
                                        $y = $shipPosition['y'] + $localY;
                                        $isInsideMap = $x >= 0 && $y >= 0 && $x < $mapSize && $y < $mapSize;
                                        $isWall = $isInsideMap && collect($obstacles)->contains(function ($cell) use ($x, $y) {
                                            if (is_array($cell) && array_key_exists('x', $cell) && array_key_exists('y', $cell)) {
                                                return (int) $cell['x'] === $x && (int) $cell['y'] === $y;
                                            }

                                            return is_array($cell) && isset($cell[0], $cell[1]) && (int) $cell[0] === $x && (int) $cell[1] === $y;
                                        });
                                        $isShip = $localX === 0 && $localY === 0;
                                        $isGoal = $isInsideMap && $target['x'] === $x && $target['y'] === $y;
                                        $isCrash = $isShip && $isWall && ($run->outcome_reason ?? null) === 'obstacle';
                                    @endphp
                                    <div class="tile {{ $isInsideMap ? '' : 'outside' }} {{ $isWall ? 'wall' : '' }} {{ $isGoal ? 'goal' : '' }} {{ $isShip ? 'ship' : '' }} {{ $isCrash ? 'crash' : '' }}" aria-label="{{ $isInsideMap ? 'Pole '.($x + 1).', '.($y + 1) : 'Poza mapą' }}" @if ($isShip) data-mini-ship="center" data-ship-marker="x" @endif></div>
                                @endfor
                            @endfor
                        </div>
                    </section>
                    <div class="helmsman-panel">
                        <div class="helmsman-header">
                            <div>
                                <p class="eyebrow">Panel sternika</p>
                                <h3>Ustaw kierunek</h3>
                            </div>
                            <span class="cooldown-badge">Blokada komendy: 15 s</span>
                        </div>

                        <form id="helmsman-form" action="{{ route('game.command.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="direction" id="helmsman-direction" value="{{ $selectedDirection }}">
                            <input type="hidden" name="ship_heading" id="helmsman-ship-heading" value="{{ $selectedDirection }}">
                            <input type="hidden" name="cooldown_seconds" id="helmsman-cooldown" value="15">
                            <input type="hidden" id="helmsman-last-command-at" value="{{ $lastCommandAt?->toIso8601String() }}">

                            <div class="compass" aria-label="Kompas kontrolny sterownika">
                                @foreach (['NW', 'N', 'NE', 'W', 'CENTER', 'E', 'SW', 'S', 'SE'] as $direction)
                                    @if ($direction === 'CENTER')
                                        <div class="dir-btn center-rose" aria-hidden="true">✦</div>
                                    @else
                                        @php $isAllowed = in_array($direction, $allowedDirections, true); @endphp
                                        <button type="button" class="dir-btn {{ $isAllowed ? '' : 'disabled' }} {{ $direction === $selectedDirection ? 'active' : '' }}" data-direction="{{ $direction }}" data-allowed="{{ $isAllowed ? 'true' : 'false' }}" @if (! $isAllowed) disabled @endif aria-pressed="{{ $direction === $selectedDirection ? 'true' : 'false' }}">{{ $direction }}</button>
                                    @endif
                                @endforeach
                            </div>

                            <div class="helmsman-summary">
                                <div>
                                    <span>Aktualny kurs</span>
                                    <strong id="selected-direction">{{ $selectedDirection }}</strong>
                                </div>
                                <div>
                                    <span>Czas do następnego ruchu</span>
                                    <strong id="decision-countdown" data-decision-countdown data-next-tick-at="{{ $nextTickAt?->toIso8601String() }}">—</strong>
                                </div>
                            </div>

                            <button class="primary" type="submit" style="width:100%;">Zatwierdź kurs</button>
                            <p class="helmsman-status" id="helmsman-status" aria-live="polite">
                                Wybierz kurs przed następnym ruchem. Po przyjęciu komendy obowiązuje osobna blokada zmiany przez 15 s.
                            </p>
                        </form>
                    </div>
                @endif

                @if (($isHost ?? false))
                    <section class="role-management" aria-labelledby="role-management-heading">
                        <p class="eyebrow">Panel kapitana</p>
                        <h3 id="role-management-heading">Zarządzanie stanowiskami</h3>
                        @if (session('success'))
                            <p class="role-feedback" role="status">{{ session('success') }}</p>
                        @endif
                        @foreach ($room->players as $member)
                            <form class="role-assignment" method="POST" action="{{ route('game.roles.assign', $member) }}">
                                @csrf
                                <label for="role-{{ $member->id }}">{{ $member->display_name }} · {{ $member->roleAssignment?->role?->name ?? 'Bez roli' }}</label>
                                <select id="role-{{ $member->id }}" name="role" required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->slug }}" @selected($member->roleAssignment?->role_id === $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button class="secondary" type="submit" aria-label="Zmień stanowisko gracza {{ $member->display_name }}">Zmień</button>
                            </form>
                        @endforeach
                    </section>
                @endif

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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('helmsman-form');
            const directionInput = document.getElementById('helmsman-direction');
            const shipHeadingInput = document.getElementById('helmsman-ship-heading');
            const selectedDirection = document.getElementById('selected-direction');
            const status = document.getElementById('helmsman-status');
            const lastCommandInput = document.getElementById('helmsman-last-command-at');
            const directionButtons = [...document.querySelectorAll('.dir-btn[data-direction]')];
            const decisionCountdowns = [...document.querySelectorAll('[data-decision-countdown]')];
            const gameShell = document.querySelector('[data-game-running]');
            const cooldownDurationMs = 15000;

            const getRemainingCooldown = () => {
                if (!lastCommandInput || !lastCommandInput.value) {
                    return 0;
                }

                const lastCommandAt = new Date(lastCommandInput.value).getTime();
                if (!Number.isFinite(lastCommandAt)) {
                    return 0;
                }

                return Math.max(0, cooldownDurationMs - (Date.now() - lastCommandAt));
            };

            const getRemainingDecisionTime = (nextTickAt) => {
                if (!nextTickAt) {
                    return 0;
                }

                const nextTickTime = new Date(nextTickAt).getTime();
                return Number.isFinite(nextTickTime) ? Math.max(0, nextTickTime - Date.now()) : 0;
            };

            const updateCooldownUi = () => {
                const cooldownActive = Boolean(form) && getRemainingCooldown() > 0;

                directionButtons.forEach((button) => {
                    const isAllowed = button.dataset.allowed === 'true';
                    const isAcceptedDirection = button.dataset.direction === directionInput.value;
                    button.disabled = !isAllowed || (cooldownActive && !isAcceptedDirection);
                    button.classList.toggle('disabled', button.disabled);
                    button.classList.toggle('active', isAcceptedDirection);
                    button.setAttribute('aria-pressed', isAcceptedDirection ? 'true' : 'false');
                });

                if (status && cooldownActive) {
                    const remainingCooldownSeconds = Math.ceil(getRemainingCooldown() / 1000);
                    status.textContent = `Kurs ${directionInput.value} został przyjęty przez załogę. Daj czas załodze na pracę: ${remainingCooldownSeconds}s.`;
                    status.classList.add('ok');
                    status.classList.remove('error');
                } else if (status?.classList.contains('ok')) {
                    status.textContent = 'Wybierz kurs przed następnym ruchem. Po przyjęciu komendy obowiązuje osobna blokada zmiany przez 15 s.';
                    status.classList.remove('ok');
                }

                decisionCountdowns.forEach((countdown) => {
                    const remainingSeconds = Math.ceil(getRemainingDecisionTime(countdown.dataset.nextTickAt) / 1000);
                    countdown.textContent = `${remainingSeconds}s`;
                });
            };

            const setSelectedDirection = (direction) => {
                if (!direction || !document.querySelector(`.dir-btn[data-direction="${direction}"]`)) {
                    return;
                }

                const chosenButton = document.querySelector(`.dir-btn[data-direction="${direction}"]`);
                if (!chosenButton || chosenButton.dataset.allowed !== 'true') {
                    return;
                }

                if (getRemainingCooldown() > 0 && direction !== directionInput.value) {
                    return;
                }

                directionInput.value = direction;
                shipHeadingInput.value = direction;
                selectedDirection.textContent = direction;

                directionButtons.forEach((button) => {
                    const isActive = button.dataset.direction === direction;
                    button.classList.toggle('active', isActive);
                    button.classList.toggle('disabled', button.dataset.allowed !== 'true');
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            directionButtons.forEach((button) => {
                if (!button.dataset.direction || button.dataset.direction === 'CENTER') {
                    return;
                }

                button.addEventListener('click', () => {
                    if (button.dataset.allowed === 'true') {
                        setSelectedDirection(button.dataset.direction);
                    }
                });
            });

            if (directionInput?.value) {
                const currentButton = document.querySelector(`.dir-btn[data-direction="${directionInput.value}"]`);
                if (currentButton) {
                    currentButton.classList.toggle('active', true);
                    currentButton.setAttribute('aria-pressed', 'true');
                }
            }

            updateCooldownUi();
            window.setInterval(updateCooldownUi, 1000);

            const nextTickAt = document.querySelector('[data-refresh-countdown]')?.dataset.nextTickAt;
            if (gameShell?.dataset.gameRunning === 'true' && nextTickAt) {
                const remainingMs = getRemainingDecisionTime(nextTickAt);
                window.setTimeout(() => window.location.reload(), Math.max(1000, remainingMs + 100));
            }

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const payload = new FormData(form);
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        status.textContent = data.message || 'Polecenie zostało odrzucone.';
                        status.classList.add('error');
                        status.classList.remove('ok');
                        return;
                    }

                    directionInput.value = data.payload.direction;
                    shipHeadingInput.value = data.payload.direction;
                    selectedDirection.textContent = data.payload.direction;
                    lastCommandInput.value = new Date().toISOString();

                    status.textContent = `Kurs ${data.payload.direction} został przyjęty przez. Poczekaj na stabilizację statku (15s.)`;
                    status.classList.add('ok');
                    status.classList.remove('error');
                    updateCooldownUi();
                } catch (error) {
                    status.textContent = 'Nie udało się wysłać danych. Spróbuj ponownie.';
                    status.classList.add('error');
                    status.classList.remove('ok');
                }
            });
        });
    </script>
</body>
</html>
