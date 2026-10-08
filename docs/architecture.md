# Architektura

## Proponowany stos

- Laravel 12 i PHP 8.5 jako aplikacja HTTP/API.
- MariaDB/MySQL do zapisu pokoi, uczestników, ról i stanu rozgrywki.
- Nginx jako serwer HTTP i PHP-FPM jako runtime aplikacji.
- Docker Compose lokalnie, aby środowisko nie zależało od PHP i Composera
  zainstalowanych na komputerze.
- Przeglądarka mobilna jako klient. WebSockety ani cron nie są wymagane dla
  MVP. Każdy odczyt stanu lub zapis komendy uruchamia serwerowe `advanceDue`,
  które rozlicza wszystkie kroki należne według zegara serwera; po okresie bez
  żądań stan nadrabia zaległe ticki przy następnym żądaniu. Klient odpytuje stan,
  aby odświeżać widok.

## Zasady odpowiedzialności

- Serwer jest źródłem prawdy o stanie pokoju i świata.
- Klient przesyła akcję, serwer sprawdza rolę i ograniczenia, a następnie
  zapisuje polecenie do wykonania w kroku symulacji.
- Sprzeczne polecenia tego samego gracza/stanowiska zastępują się zgodnie z
  regułą ostatniej akcji.
- Widoki otrzymują tylko informacje przeznaczone dla danej roli. Kapitan widzi
  pełną mapę; ekran główny pokazuje statek i bliskie otoczenie.
- Reset bezczynnego pokoju po godzinie powinien być realizowany po stronie
  serwera, nie przez timer działający wyłącznie w przeglądarce.

## Proces gry i role

Przepływ jest prosty i liniowy, ale z rozdzieleniem odpowiedzialności:

1. Gospodarz tworzy pokój i uruchamia grę po obsadzeniu Kapitana i Sternika.
2. Serwer tworzy nową serię i pierwszą partię z mapą, statkiem i stanem początkowym.
3. Kapitan widzi pełną mapę, a Sternik tylko swoje polecenia sterujące.
4. Sternik wysyła kierunek; serwer zapisuje polecenie, sprawdza cooldown i
   podmienia wcześniejsze nieprzetworzone polecenie w tej samej rundzie.
5. `advanceDue` rozlicza ticki po czasie serwera; ruch, kolizja z granicą,
   kolizja z przeszkodą i osiągnięcie celu zapisują się jako osobne ticki.
6. Po zakończeniu rundy gospodarz widzi podsumowanie, może uruchomić następną
   partię, a po trzeciej rundzie podpisuje się zakończenie całej serii.

To są kierunki projektowe, nie zaimplementowane jeszcze kontrakty API.

Kolejność prac, kryteria ukończenia i bramka stabilnych testów lokalnych są
opisane w [planie wdrożenia](development-and-deployment.md#plan-wdrożenia-rozgrywki).
Do czasu zamknięcia etapu reguł mechaniki nie należy utrwalać w schemacie
nieuzgodnionych założeń, takich jak rozmiar mapy lub prędkość statku.

## Schemat lobby

Lobby korzysta z następujących tabel:

- `roles` przechowuje słownik stanowisk i flagę dostępności. Seed aktywuje
  Kapitana i Sternika; przyszłe stanowiska są zapisane jako nieaktywne.
- `players` identyfikuje anonimowego gracza przez losowy token sesji zapisany w
  bazie wyłącznie jako skrót.
- `game_rooms` przechowuje pokój, jego kod i stan.
- `room_players` jest bieżącą listą członków pokoju, ich pseudonimów i
  uprawnienia gospodarza.
- `room_player_roles` wiąże członka pokoju z rolą; ograniczenia unikalności
  pozwalają przypisać jedną rolę graczowi i jednego gracza do roli w pokoju.
- `player_join_logs` zachowuje historię skutecznych wejść, także ponownych.
- `player_actions` rejestruje dostępne akcje lobby, ich wynik i kontekst.
- `player_bans` przechowuje blokady po graczu lub haszu IP, powód, opcjonalny
  termin wygaśnięcia i opcjonalnego wystawcę. Middleware lobby sprawdza
  aktywność blokady na każdym żądaniu i loguje zablokowane próby.

Adres IP w logach jest haszowany kluczem aplikacji; user-agent pozostaje
zapisany do analizy nadużyć. Ban po graczu działa również po zmianie adresu IP;
ban po IP może objąć kilka osób korzystających ze wspólnego łącza. Panel
administracyjny do wystawiania i cofania banów pozostaje do dodania.
## Stan i historia rozgrywki

`player_actions` pozostaje ogólnym audytem zdarzeń użytkownika, np. startu gry,
zmiany roli i odrzuconych akcji lobby. Nie jest kolejką poleceń ani źródłem
stanu symulacji. Mechanika korzysta z osobnych rekordów:

- `game_series` grupuje trzy partie jednego pokoju i przechowuje postęp oraz
  wynik serii.
- `game_runs` opisuje jedną partię: status i wynik, numer w serii, mapę wraz
  z seedem, cel oraz czas rozpoczęcia i zakończenia.
- `game_states` przechowuje jeden bieżący snapshot partii: pozycję statku,
  kierunek dziobu, numer ticka i liczbę udanych ruchów.
- `game_commands` jest trwałym dziennikiem poleceń z autorem, rolą, payloadem,
  czasem przyjęcia i statusem wykonania. Zastąpione polecenie pozostaje w
  historii ze statusem `superseded`.
- `game_ticks` jest append-only historią kroków świata z numerem ticka, stanem
  przed i po ruchu oraz zastosowanymi poleceniami.

Każda partia ma własną mapę i stan. Porażka lub zwycięstwo kończy partię, ale
nie usuwa jej danych; gospodarz ręcznie uruchamia następną z serii. Po trzeciej
partii seria otrzymuje końcowe podsumowanie. Cooldown Sternika wynosi 15 sekund,
a krok symulacji 20 sekund. Serwer waliduje komendy i jest jedynym źródłem
prawdy o pozycji.

Tick musi być idempotentny: rekord stanu jest blokowany w transakcji, a numer
ticka jest unikalny w obrębie partii. Szczegóły wyzwalania ticków zależą od
możliwości schedulera na docelowym hostingu; decyzja nie może przenosić
rozstrzygania symulacji do przeglądarki.
## Środowisko lokalne

`docker-compose.yaml` uruchamia trzy usługi: `app` (PHP-FPM), `nginx` i `db`
(MariaDB). Kod źródłowy jest montowany z repozytorium; baza przechowuje dane w
wolumenie `db-data`.

## Hosting

Docelowy adres aplikacji to `https://andrzejnogala.pl/crew`. Hosting ma PHP 8.5.
Przed wdrożeniem trzeba potwierdzić dostępność rozszerzenia PDO MySQL, bazy
MySQL/MariaDB oraz konfigurację, która kieruje żądania pod ścieżką `/crew` do
katalogu `public` Laravel. Nie należy wystawiać katalogu aplikacji ani pliku
`.env` bezpośrednio do WWW.

Lokalny Nginx i produkcyjny serwer hostingu mają różne konfiguracje routingu;
szczegóły wdrożenia są opisane w
[instrukcji developerskiej i wdrożeniowej](development-and-deployment.md).