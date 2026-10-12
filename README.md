# Crew

Kooperacyjna gra przeglądarkowa o załodze pirackiego statku. Każdy gracz ma
inne informacje i możliwości, a cała załoga prowadzi jeden statek do celu.

## Wymagania

- Docker Desktop lub Docker Engine z wtyczką Docker Compose
- Git

PHP i Composer są dostarczane przez kontener; nie trzeba instalować ich na
komputerze.

## Pierwsze uruchomienie

W katalogu repozytorium zbuduj kontener PHP i utwórz szkielet Laravel. Polecenie
zachowuje istniejące pliki repozytorium, takie jak ten README i dokumentację.

```sh
docker compose build app
docker compose run --rm --no-deps app sh -lc 'composer create-project "laravel/laravel:^12.0" /tmp/crew && cp -an /tmp/crew/. /var/www/html/'
```

Następnie uruchom aplikację i bazę:

```sh
docker compose up -d
docker compose exec app php artisan key:generate
```

Otwórz [http://localhost:8080](http://localhost:8080). Logi usług sprawdzisz
poleceniem `docker compose logs -f app nginx db`; zatrzymanie środowiska:
`docker compose down`. Dane MariaDB pozostają w wolumenie `db-data`.

Migracje i słownik ról utworzysz poleceniem:

```sh
docker compose exec app php artisan migrate --seed
```

Seedowanie dodaje aktywne role Kapitana i Sternika oraz nieaktywne role
planowane na przyszłość.

## Dane lokalne

Kontener udostępnia PHP-FPM pod Nginx oraz MariaDB dostępną wewnątrz sieci
Compose jako `db:3306`. Domyślne poświadczenia z `docker-compose.yaml` są
wyłącznie do lokalnego developmentu i nie mogą trafić na serwer produkcyjny.
Port aplikacji na komputerze to `8080`.

## Dokumentacja

- [Opis projektu](docs/overview.md)
- [Zasady gry i MVP](docs/gameplay.md)
- [Architektura i decyzje techniczne](docs/architecture.md)
- [Uruchomienie i wdrożenie](docs/development-and-deployment.md)
- [Plan rozbudowy multiplayer](docs/multiplayer-role-expansion-plan.md)
- [Mapa dokumentacji w Obsidian Canvas](docs/crew.canvas)
- [Specyfikacje OpenSpec](openspec/project.md)
- [Proces propozycji OpenSpec](openspec/changes/README.md)

## Współpraca nad zmianami

OpenSpec opisuje wymagania i uzgodniony zakres zmian. `openspec/specs/`
zawiera aktualne zachowanie systemu, a `openspec/changes/` — propozycje
zmian, które jeszcze nie zostały wdrożone. Przed zmianą zachowania gry lub
architektury opisz cel, kryteria akceptacji i aktualizację specyfikacji.
Przy małej zmianie technicznej bez zmiany zachowania wystarczy implementacja
i testy; nie twórz pustej propozycji tylko dla formalności. Po wdrożeniu
zaktualizuj specyfikację bazową i zarchiwizuj zaakceptowaną zmianę.

Zgłoszenie zadania będzie najłatwiejsze do realizacji, jeśli zawrze:

```text
Cel: co użytkownik ma móc zrobić albo jaki problem rozwiązać?
Obecne zachowanie: co dzieje się teraz i gdzie to widać?
Oczekiwane zachowanie: co ma się zmienić?
Kryteria akceptacji: konkretne warunki, które można sprawdzić lub przetestować.
Ograniczenia: czego nie zmieniać, jakie są zależności lub wymagania UX?
Poza zakresem: jakie pokrewne rzeczy świadomie odkładamy?
```

Nie trzeba uzupełniać pól, które nie mają zastosowania. Warto dołączyć kroki
odtworzenia, zrzuty ekranu, przykładowe dane lub seed mapy, gdy pomagają
wyjaśnić problem. Jeśli wymaganie dopuszcza kilka istotnie różnych rozwiązań,
opisz preferencję albo poproś o krótkie porównanie przed implementacją.

Testy i polecenia developerskie uruchamiaj w kontenerze, nie przez lokalny PHP:

```sh
docker compose build app
docker compose run --rm --no-deps \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  app php artisan test
```

Testy korzystają z SQLite in-memory i nie wymagają działającej usługi MariaDB.
Kontener aplikacji używa PHP 8.5.

## Stan prac

Lobby pozwala tworzyć nazwane pokoje, dołączać kodem i zajmować wolne role.
Kapitan i Sternik są aktywni; Lokalizator, Bocianie gniazdo i pozostałe role są
widoczne, ale wyłączone. Działa serwerowa rozgrywka tickowa, mapa 20×20,
sterowanie Sternika, mapa Kapitana, lokalny podgląd 5×5 Sternika oraz partie
grupowane w serie po trzy. Rozbudowa o role informacyjne i niepełną mapę
Kapitana jest planowana w [planie multiplayer](docs/multiplayer-role-expansion-plan.md).
