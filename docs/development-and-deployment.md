# Uruchomienie i wdrożenie

## Lokalnie przez Docker

Instrukcja pierwszego uruchomienia znajduje się w głównym [README](../README.md).
Kontenery korzystają z PHP 8.5-FPM, Nginx i MariaDB 11.4. Aplikacja będzie
dostępna na `http://localhost:8080`, a dane bazy pozostaną w wolumenie
`db-data`.

Przydatne polecenia:

```sh
docker compose up -d
docker compose logs -f app nginx db
docker compose exec app php artisan migrate --seed
docker compose down
```

`docker compose down` zatrzymuje i usuwa kontenery, ale pozostawia wolumen bazy.
Usunięcie danych wymaga jawnego `docker compose down -v`.

## Wdrożenie na home.pl

Założenia wdrożenia:

- PHP 8.5 jest dostępne na hostingu.
- Adres publiczny aplikacji: `https://andrzejnogala.pl/crew`.
- Kod aplikacji musi pozostać poza katalogiem publicznym, a katalog WWW ma
  wskazywać na `public` Laravel lub poprawnie przekazywać do niego żądania.
- Konfiguracja produkcyjna (`APP_ENV=production`, `APP_DEBUG=false`, klucze i
  hasła) musi być ustawiona oddzielnie od konfiguracji lokalnej.

Przed wdrożeniem do potwierdzenia w panelu hostingu: obsługa PDO MySQL,
dostępna baza i jej wersja, możliwość zmiany katalogu publicznego lub reguł
rewrite w podfolderze oraz sposób uruchamiania komend Artisan/migracji.

Docker Compose jest środowiskiem developerskim. Nie zakładamy, że będzie można
uruchomić go na hostingu współdzielonym.

## Stan funkcji

Lobby, pokoje, anonimowi gracze, wybór ról i logi zdarzeń są dostępne po
uruchomieniu migracji i seederów. Mechanikę gry, mapę oraz widoki kapitana i
sternika dodamy w kolejnych etapach.