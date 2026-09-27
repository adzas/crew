# Architektura

## Proponowany stos

- Laravel 12 i PHP 8.5 jako aplikacja HTTP/API.
- MariaDB/MySQL do zapisu pokoi, uczestników, ról i stanu rozgrywki.
- Nginx jako serwer HTTP i PHP-FPM jako runtime aplikacji.
- Docker Compose lokalnie, aby środowisko nie zależało od PHP i Composera
  zainstalowanych na komputerze.
- Przeglądarka mobilna jako klient. WebSockety nie są wymagane dla spokojnej
  symulacji aktualizowanej co 20 sekund; częstotliwość odpytywania trzeba
  ustalić przy implementacji.

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

To są kierunki projektowe, nie zaimplementowane jeszcze kontrakty API.

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

Adres IP w logach jest haszowany kluczem aplikacji; user-agent pozostaje
zapisany do analizy nadużyć. Logi nie blokują graczy samodzielnie. Przed
wdrożeniem moderacji warto dodać `player_bans` z identyfikatorem gracza lub
skrótem IP, powodem i opcjonalnym terminem wygaśnięcia oraz egzekwować blokadę
na serwerze.

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