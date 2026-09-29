<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#122b2a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($room)
        <meta http-equiv="refresh" content="10">
    @endif
    <title>Załoga | Crew</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #142827;
            --muted: #58706b;
            --paper: #f3f1e7;
            --paper-light: #fbfaf5;
            --sea: #dce8dd;
            --line: #c7d1c4;
            --green: #1b5949;
            --green-dark: #123e36;
            --brass: #b87932;
            --coral: #a44436;
            font-family: Georgia, 'Palatino Linotype', 'Book Antiqua', serif;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            background-color: var(--paper);
            background-image: repeating-linear-gradient(0deg, transparent 0 43px, rgb(20 40 39 / 4%) 44px), repeating-linear-gradient(90deg, transparent 0 43px, rgb(20 40 39 / 4%) 44px);
        }
        button, input { font: inherit; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 64px;
            padding: 12px clamp(18px, 5vw, 72px);
            color: #f8f4e8;
            background: var(--green-dark);
            border-bottom: 3px solid var(--brass);
        }
        .brand { display: flex; align-items: center; gap: 11px; font: 700 17px/1.1 Georgia, serif; letter-spacing: 0; }
        .brand-mark { display: grid; width: 32px; aspect-ratio: 1; place-items: center; border: 1px solid #d9b26a; border-radius: 50%; color: #e5c684; font-size: 17px; }
        .top-note { color: #c3d0c4; font: 12px/1.3 ui-sans-serif, system-ui, sans-serif; }
        main { width: min(1120px, calc(100% - 36px)); margin: 0 auto; padding: clamp(32px, 7vh, 76px) 0 56px; }
        .eyebrow { margin: 0 0 12px; color: var(--green); font: 700 11px/1.2 ui-sans-serif, system-ui, sans-serif; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { max-width: 720px; margin: 0; font-size: clamp(36px, 6vw, 64px); font-weight: 500; line-height: .98; letter-spacing: 0; }
        .intro { max-width: 570px; margin: 16px 0 36px; color: var(--muted); font: 16px/1.55 ui-sans-serif, system-ui, sans-serif; }
        .join-panel { max-width: 680px; padding: clamp(22px, 4vw, 38px); background: var(--paper-light); border: 1px solid var(--line); border-top: 4px solid var(--brass); box-shadow: 0 18px 50px rgb(20 40 39 / 9%); }
        .join-panel h2, .section-heading { margin: 0 0 18px; font-size: 24px; font-weight: 500; letter-spacing: 0; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { display: block; margin-bottom: 7px; color: var(--muted); font: 700 11px/1.3 ui-sans-serif, system-ui, sans-serif; letter-spacing: .5px; text-transform: uppercase; }
        input { width: 100%; min-height: 48px; padding: 0 13px; color: var(--ink); background: white; border: 1px solid #aabbb0; border-radius: 2px; outline: none; }
        input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgb(27 89 73 / 13%); }
        .hint { min-height: 17px; margin: 6px 0 0; color: var(--coral); font: 12px/1.4 ui-sans-serif, system-ui, sans-serif; }
        .primary, .role-button, .copy-button { min-height: 44px; padding: 0 16px; border: 0; border-radius: 2px; cursor: pointer; font: 700 13px/1.2 ui-sans-serif, system-ui, sans-serif; transition: background-color .16s ease, transform .16s ease; }
        .primary { display: inline-flex; align-items: center; gap: 10px; margin-top: 18px; color: white; background: var(--green); }
        .primary:hover, .role-button:hover:not(:disabled) { background: var(--green-dark); transform: translateY(-1px); }
        .error-banner, .success-banner { margin: 0 0 18px; padding: 12px 14px; border-left: 3px solid var(--coral); color: #762f27; background: #f7e8df; font: 14px/1.45 ui-sans-serif, system-ui, sans-serif; }
        .success-banner { border-color: var(--green); color: var(--green-dark); background: var(--sea); }
        .doppler-panel { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: end; gap: 14px; margin-top: 24px; padding: 18px; background: #e8eadb; border: 1px solid #b9c2a8; border-left: 4px solid var(--brass); }
        .doppler-status { grid-column: 1 / -1; margin: 0; color: var(--green-dark); font: 700 12px/1.4 ui-sans-serif, system-ui, sans-serif; }
        .doppler-status strong { color: #7a4c19; }
        .doppler-panel label { margin-bottom: 6px; }
        .doppler-panel select { width: 100%; min-height: 44px; padding: 0 10px; color: var(--ink); background: white; border: 1px solid #aabbb0; border-radius: 2px; font: 14px ui-sans-serif, system-ui, sans-serif; }
        .doppler-panel button { min-height: 44px; padding: 0 14px; border: 1px solid var(--green); border-radius: 2px; color: white; background: var(--green); cursor: pointer; font: 700 12px ui-sans-serif, system-ui, sans-serif; }
        .doppler-panel .doppler-stop { color: var(--green-dark); background: transparent; border-color: #aabbb0; }
        .room-header { display: flex; align-items: end; justify-content: space-between; gap: 24px; padding-bottom: 22px; border-bottom: 1px solid var(--line); }
        .room-title { margin: 0; font-size: clamp(32px, 5vw, 52px); font-weight: 500; line-height: 1; letter-spacing: 0; }
        .room-code { display: flex; align-items: center; gap: 12px; }
        .code-label { display: block; margin-bottom: 4px; color: var(--muted); font: 700 10px/1.2 ui-sans-serif, system-ui, sans-serif; letter-spacing: 1px; text-transform: uppercase; }
        .code-value { font: 700 22px/1.1 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 2px; }
        .copy-button { min-width: 110px; color: var(--green-dark); background: var(--sea); border: 1px solid #aec4b4; }
        .copy-button:hover { background: #c9ddcf; }
        .lobby-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr); gap: clamp(24px, 5vw, 56px); margin-top: 34px; }
        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; padding-bottom: 12px; border-bottom: 2px solid var(--ink); }
        .section-heading small { color: var(--muted); font: 12px/1.2 ui-sans-serif, system-ui, sans-serif; }
        .roster, .role-table { width: 100%; border-collapse: collapse; text-align: left; }
        .roster th, .role-table th { padding: 9px 8px; color: var(--muted); border-bottom: 1px solid var(--line); font: 700 10px/1.2 ui-sans-serif, system-ui, sans-serif; letter-spacing: .7px; text-transform: uppercase; }
        .roster td, .role-table td { padding: 13px 8px; border-bottom: 1px solid var(--line); vertical-align: middle; font: 14px/1.35 ui-sans-serif, system-ui, sans-serif; }
        .roster th:first-child, .roster td:first-child, .role-table th:first-child, .role-table td:first-child { padding-left: 0; }
        .host-tag, .you-tag { display: inline-block; margin-left: 7px; padding: 3px 6px; color: #72501f; background: #f0e2bf; font: 700 9px/1 ui-sans-serif, system-ui, sans-serif; text-transform: uppercase; vertical-align: 1px; }
        .you-tag { color: var(--green-dark); background: var(--sea); }
        .role-name { display: block; font-weight: 700; }
        .role-description { display: block; margin-top: 4px; color: var(--muted); font-size: 12px; line-height: 1.4; }
        .role-status { color: var(--muted); font-size: 12px; }
        .role-button { min-width: 104px; color: white; background: var(--green); }
        .role-button:disabled { color: #58706b; background: #e3e8df; cursor: not-allowed; }
        .unavailable { color: #79847c; }
        .room-foot { margin-top: 24px; color: var(--muted); font: 12px/1.5 ui-sans-serif, system-ui, sans-serif; }
        @media (max-width: 760px) {
            .top-note { display: none; }
            main { padding-top: 38px; }
            .intro { margin-bottom: 26px; }
            .form-grid, .lobby-grid { grid-template-columns: 1fr; }
            .doppler-panel { grid-template-columns: 1fr; }
            .doppler-status { grid-column: auto; }
            .room-header { align-items: start; flex-direction: column; }
            .lobby-grid { gap: 38px; }
            .role-table td:last-child, .role-table th:last-child { text-align: right; }
            .role-button { min-width: 94px; padding: 0 10px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand"><span class="brand-mark" aria-hidden="true">C</span><span>CREW <span style="color:#d9b26a">/</span> ZAŁOGA</span></div>
        <span class="top-note">Jeden statek. Wspólny kurs.</span>
    </header>

    <main>
        @if ($room && $roomPlayer)
            <p class="eyebrow">Pokój {{ $room->status === 'waiting' ? 'oczekuje na załogę' : 'w trakcie gry' }}</p>
            <div class="room-header">
                <div>
                    <h1 class="room-title">Zbiórka załogi</h1>
                    <p class="intro" style="margin-bottom:0">Wybierz wolne stanowisko. Skład pokoju odświeża się automatycznie.</p>
                </div>
                <div class="room-code">
                    <div>
                        <span class="code-label">Kod zaproszenia</span>
                        <span class="code-value" id="room-code">{{ $room->code }}</span>
                    </div>
                    <button class="copy-button" type="button" id="copy-invite" aria-label="Skopiuj link zaproszenia">Skopiuj link</button>
                </div>
            </div>

            @if ($errors->any())
                <div class="error-banner" role="alert">{{ $errors->first() }}</div>
            @endif
            @if (session('error'))
                <div class="error-banner" role="alert">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="success-banner" role="status">{{ session('success') }}</div>
            @endif

            @if ($dopplerEnabled)
                <section class="doppler-panel" aria-label="Lokalna symulacja roli">
                    @if ($dopplerRole)
                        <p class="doppler-status" role="status">TRYB DOPPLER AKTYWNY <strong>· działasz jako {{ $dopplerRole->name }}</strong></p>
                    @else
                        <p class="doppler-status">TRYB DOPPLER · lokalna symulacja roli gospodarza</p>
                    @endif
                    <form method="POST" action="{{ route('lobby.doppler.switch') }}" id="doppler-form">
                        @csrf
                        <label for="doppler-role">Symuluj stanowisko</label>
                        <select id="doppler-role" name="role" required>
                            @foreach ($roles->where('is_active', true) as $role)
                                <option value="{{ $role->slug }}" @selected($dopplerRole?->id === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <button type="submit" form="doppler-form">Przełącz rolę</button>
                    @if ($dopplerRole)
                        <form method="POST" action="{{ route('lobby.doppler.stop') }}">
                            @csrf
                            <button class="doppler-stop" type="submit">Wróć do siebie</button>
                        </form>
                    @endif
                </section>
            @endif

            <div class="lobby-grid">
                <section aria-labelledby="crew-heading">
                    <h2 class="section-heading" id="crew-heading">Załoga <small>{{ $room->players->count() }} {{ $room->players->count() === 1 ? 'osoba' : 'osób' }}</small></h2>
                    <table class="roster">
                        <thead><tr><th scope="col">Gracz</th><th scope="col">Stanowisko</th></tr></thead>
                        <tbody>
                            @foreach ($room->players as $member)
                                <tr>
                                    <td>
                                        {{ $member->display_name }}
                                        @if ($member->is_host)<span class="host-tag">Gospodarz</span>@endif
                                        @if ($member->id === $roomPlayer->id)<span class="you-tag">Ty</span>@endif
                                    </td>
                                    <td>{{ $member->roleAssignment?->role?->name ?? 'Nie wybrano' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="room-foot">Link zaproszenia prowadzi do tego pokoju. Gospodarz jest oznaczony niezależnie od wybranego stanowiska.</p>
                </section>

                <section aria-labelledby="roles-heading">
                    <h2 class="section-heading" id="roles-heading">Stanowiska <small>wybierz jedno</small></h2>
                    <table class="role-table">
                        <thead><tr><th scope="col">Rola</th><th scope="col">Dostępność</th></tr></thead>
                        <tbody>
                            @foreach ($roles as $role)
                                @php
                                    $holder = $room->players->first(fn ($member) => $member->roleAssignment?->role_id === $role->id);
                                    $isMine = $holder?->id === $roomPlayer->id;
                                    $isTaken = $holder !== null && ! $isMine;
                                @endphp
                                <tr class="{{ $role->is_active ? '' : 'unavailable' }}">
                                    <td>
                                        <span class="role-name">{{ $role->name }}</span>
                                        <span class="role-description">{{ $role->description }}</span>
                                    </td>
                                    <td>
                                        @if (! $role->is_active)
                                            <span class="role-status">Wkrótce</span>
                                        @elseif ($isMine)
                                            <button class="role-button" type="button" disabled>Wybrano</button>
                                        @elseif ($isTaken)
                                            <span class="role-status">{{ $holder->display_name }}</span>
                                        @else
                                            <form method="POST" action="{{ route('lobby.roles.claim', $role->slug) }}">
                                                @csrf
                                                <button class="role-button" type="submit">Wybierz</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            </div>
        @else
            <p class="eyebrow">Załoga czeka na kapitana</p>
            <h1>Wypłyńmy<br>razem.</h1>
            <p class="intro">Stwórz pokój jako gospodarz albo dołącz do istniejącej załogi. Wybierzesz stanowisko po wejściu do lobby.</p>

            <section class="join-panel" aria-labelledby="join-heading">
                <h2 id="join-heading">Wejdź na pokład</h2>
                @if ($errors->any())
                    <div class="error-banner" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('lobby.join') }}">
                    @csrf
                    <div class="form-grid">
                        <div>
                            <label for="display_name">Twój pseudonim</label>
                            <input id="display_name" name="display_name" type="text" minlength="2" maxlength="24" autocomplete="nickname" value="{{ old('display_name') }}" placeholder="np. Czarna Mewa" required>
                            @error('display_name')<p class="hint">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="room_code">Kod pokoju <span style="font-weight:400;text-transform:none">(opcjonalnie)</span></label>
                            <input id="room_code" name="room_code" type="text" maxlength="6" minlength="6" autocomplete="off" value="{{ old('room_code', request('room')) }}" placeholder="Nowy pokój bez kodu" style="text-transform:uppercase">
                            @error('room_code')<p class="hint">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button class="primary" type="submit">Dołącz do załogi <span aria-hidden="true">→</span></button>
                </form>
            </section>
        @endif
    </main>

    @if ($room && $roomPlayer)
        <script>
            document.getElementById('copy-invite').addEventListener('click', async (event) => {
                const inviteUrl = `${window.location.origin}${window.location.pathname}?room=${document.getElementById('room-code').textContent}`;
                try {
                    await navigator.clipboard.writeText(inviteUrl);
                    event.currentTarget.textContent = 'Skopiowano';
                } catch {
                    window.prompt('Skopiuj link zaproszenia:', inviteUrl);
                }
                fetch(@json(route('lobby.actions', 'invite.copy')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
            });
        </script>
    @endif
</body>
</html>