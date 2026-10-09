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

## Stabilne testy lokalne

Testy Feature i Unit korzystają z SQLite in-memory skonfigurowanego w
`phpunit.xml`. Obraz PHP zawiera `pdo_sqlite`, więc testy nie potrzebują
działającej MariaDB ani nie zmieniają lokalnej bazy developerskiej.

Pełną suitę uruchom bez działającej MariaDB:

```sh
docker compose build app
docker compose run --rm --no-deps \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  app php artisan test
```

Etap stabilizacji uznajemy za zakończony, gdy polecenie działa na świeżym
kontenerze, uruchamia całą suitę, nie wymaga ręcznej instalacji rozszerzeń,
nie łączy się z usługą `db` i pozostawia lokalną bazę developerską bez zmian.
Testy integracyjne wymagające MariaDB powinny być osobną, jawnie uruchamianą
grupą.

### Lokalna symulacja ról gospodarzem

Tryb Doppler pozwala gospodarzowi testować aktywne role w ramach jednej sesji.
Nie zmienia składu pokoju ani przypisanej gospodarzowi roli. Włącz go wyłącznie
lokalnie, ustawiając `GAME_DOPPLER_ENABLED=true` w `.env`; domyślna wartość to
`false`. Funkcja jest dodatkowo ograniczona do środowisk `local` i `testing`,
więc nie zadziała w `production`, nawet jeśli flaga zostanie tam ustawiona.

W danej chwili można symulować jedną rolę. Inne karty tej samej przeglądarki
współdzielące sesję zobaczą to samo przełączenie. Logi akcji zachowują
gospodarza jako autora i zapisują osobno rolę symulowaną.

## Plan wdrożenia rozgrywki

Rozwój gry jest podzielony na przyrosty. Każdy etap kończy się testami
akceptacyjnymi oraz kontrolą formatowania i diagnostyki. Nie rozpoczynamy
kolejnego etapu, jeśli jego kontrakt lub testy poprzedniego nie są stabilne.

1. **Stabilizacja testów lokalnych.** Ukończona: PHP zawiera `pdo_sqlite`, a
   pełna suita uruchamia się na SQLite in-memory.
2. **Reguły MVP.** Ukończone i pokryte testami: mapa 20×20, ruch naprzód z
   utrzymaniem kursu lub skrętem o 45°, kolizje kończące partię, start po
   obsadzeniu Kapitana i Sternika, cooldown 15 sekund i tick co 20 sekund.
   Statek ma stałą prędkość, bez osobnego systemu obrażeń.
3. **Trwały stan i start partii.** Ukończone: serie i partie mają trwałe
   rekordy, mapy są generowane z zapisanym seedem, a gospodarz uruchamia grę
   po obsadzeniu wymaganych ról.
4. **Polecenia i ticki.** Ukończone dla Sternika: serwerowa autoryzacja,
   walidacja kursu i cooldownu, historia zastąpionych komend, transakcyjne
   ticki idempotentne z ponawianiem transakcji po deadlocku.
5. **Widoki ról.** Ukończone w obecnym zakresie: Kapitan otrzymuje pełną mapę,
   Sternik mapę lokalną 5×5 i panel sterowania. Widok pokazuje X statku oraz
   licznik odświeżany względem `next_tick_at`. Niepełna mapa Kapitana i raporty
   dodatkowych ról są osobnym, przyszłym etapem.
6. **Wynik i kolejne partie.** Ukończone częściowo: ekran wyniku pojedynczej
   partii i możliwość uruchomienia kolejnej działają. Seria obejmuje trzy
   partie; podsumowanie zbiorcze i reset pokoju po godzinie bezczynności
   pozostają do wdrożenia.
7. **Rozbudowa ról informacyjnych.** Zaplanowana; szczegółowa checklista,
   zatwierdzone decyzje i status fundamentów znajdują się w
   [planie rozbudowy multiplayer](multiplayer-role-expansion-plan.md).

Aktualna implementacja nie ogranicza liczby uczestników w pokoju, mimo że
docelowa załoga ma liczyć 4–6 graczy. Rozliczanie ticków nadrabia zaległości
przy odczycie gry lub zapisie komendy; bez ruchu HTTP nie ma gwarancji
ciągłego postępu. Kolizja z przeszkodą i wyjście poza granicę zapisują pozycję
końcową odmiennie; szczegóły opisano w [zasadach gry](gameplay.md).

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

Lobby, pokoje, anonimowi gracze, aktywne role, nazwy pokoi, mechanika serwerowa,
mapa, polecenia Kapitana/Sternika i wynik pojedynczej partii są dostępne po
uruchomieniu migracji i seederów. Dodatkowe role informacyjne, niepełna mapa
Kapitana, podsumowanie serii i reset bezczynności są planowane, ale
niezaimplementowane.