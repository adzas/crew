# Crew — kontekst projektu

## Cel

Crew to kooperacyjna gra przeglądarkowa o załodze pirackiego statku. Gracze
mają różne informacje i uprawnienia, a serwer prowadzi wspólny stan rozgrywki.

## Stos i uruchamianie

- Laravel 12, PHP 8.5, Blade i JavaScript.
- MariaDB 11.4 dla aplikacji; testy Feature i Unit korzystają z SQLite
  in-memory skonfigurowanego w `phpunit.xml`.
- Docker Compose uruchamia usługi `app`, `nginx` i `db`. PHP i Composer
  dostarcza kontener `app`.
- Pełny zestaw testów uruchamiaj w kontenerze:

  ```sh
  docker compose run --rm --no-deps \
    -e APP_ENV=testing \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=:memory: \
    app php artisan test
  ```

Szczegóły znajdują się w [README](../README.md) i
[dokumentacji developerskiej](../docs/development-and-deployment.md).

## Zasady architektury

- Serwer jest źródłem prawdy o stanie pokoju i symulacji.
- Zachowuj istniejące granice Laravel: kontrolery obsługują HTTP, modele
  reprezentują trwałe dane, a logika symulacji należy do `app/Services/Game/`.
- Nie przenoś ani nie przebudowuj niezwiązanych części aplikacji przy
  realizacji pojedynczej funkcji.
- Zmiany zachowania opisz najpierw jako propozycję OpenSpec; przy niewielkich
  poprawkach technicznych bez zmiany zachowania propozycja nie jest wymagana.
- Wymagania bazowe są w `openspec/specs/`, aktywne zmiany w
  `openspec/changes/`, a wdrożone zmiany należy archiwizować.
- Dodawaj lub aktualizuj testy, które sprawdzają samo wymagane zachowanie,
  a nie tylko szczegóły implementacji.
- Dokumentacja produktu i wdrożenia znajduje się w `docs/`.

## Aktualny zakres gry

- Gra korzysta z mapy 20×20 generowanej deterministycznie z seeda.
- Statek ma osiem kierunków ruchu, a przeszkody i granice powodują kolizję.
- Aktywne role załogowe to Kapitan i Sternik. Kapitan widzi pełną mapę;
  Sternik widzi lokalny podgląd 5×5 i ustawia kurs. Lokalizator, Bocianie
  gniazdo i inne role są zdefiniowane, ale pozostają nieaktywne do czasu
  wdrożenia i przetestowania ich kontraktów.
- Partie zapisują własną mapę, stan, ticki i historię komend.

Szczegóły wymagań są podzielone na specyfikacje możliwości w
`openspec/specs/`; dokumentacja gameplay opisuje szerszy kontekst MVP.
