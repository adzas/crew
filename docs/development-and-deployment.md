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

1. **Stabilizacja testów lokalnych.** Zakończona: obraz PHP zawiera `pdo_sqlite`,
   a opisana wyżej komenda uruchamia suitę na SQLite in-memory.
2. **Reguły MVP.** Doprecyzować mapę, ruch, kolizje, uszkodzenia, koniec gry,
   cooldown i harmonogram. Ustalone już decyzje: mapa 20x20, ruch tylko w przód
   względem dziobu w ośmiu kierunkach, kolizja z granicą lub przeszkodą kończy
   rozgrywkę, brak osobnego systemu obrażeń w prostym MVP, start wymaga Kapitana
   i Sternika, a serwer jest źródłem prawdy. Cooldown polecenia wynosi 15 sekund,
   a czas gry liczy się wyłącznie po stronie serwera. Kryterium: sporne
   przypadki mechaniki dają się opisać jednoznacznymi testami.
3. **Start i trwały stan partii.** Dodać zapis pojedynczego uruchomienia gry,
   mapy i początkowego stanu statku. Start dostępny tylko gospodarzowi,
   transakcyjny i dozwolony raz po obsadzeniu obu ról. Kryterium: próby
   nieuprawnione, przedwczesne i powtórne nie zmieniają stanu pokoju.
4. **Polecenia graczy.** Zapisywać polecenia niezależnie od ogólnego logu akcji;
   serwer sprawdza rolę, stan gry, poprawność danych, cooldown 5 sekund oraz
   zastępowanie wcześniejszego polecenia dotyczącego tego samego elementu.
   Kryterium: testy poprawnych i odrzuconych poleceń oraz łączenia poleceń ról.
5. **Symulacja świata.** Wydzielić deterministyczny krok serwerowy co 20 sekund,
   zastosować polecenia, ruch, kolizje i uszkodzenia oraz zabezpieczyć tick
   przed podwójnym wykonaniem. Kryterium: reguły domenowe mają testy brzegowe,
   a opóźnione lub powtórne wywołanie nie dubluje skutków.
6. **Ekrany ról.** Dodać ekran główny, pełną mapę Kapitana i panel Sternika.
   Odczyt stanu filtruje dane po roli; klient wysyła komendy, lecz sam nie
   rozstrzyga symulacji. Kryterium: testy uprawnień i braku wycieku informacji,
   a ręczny test w dwóch kartach potwierdza współpracę graczy.
7. **Koniec i utrzymanie.** Dodać zwycięstwo, porażkę, blokadę dalszych komend,
   reset po godzinie rzeczywistej bezczynności oraz uruchamianie ticków i
   porządkowania zgodne z możliwościami hostingu. Kryterium: pełny test od
   lobby do końca gry i nowego uruchomienia oraz sprawdzenie po odświeżeniu.

Etap 1 jest bramką dla całego rozwoju. Etap 2 blokuje implementację stanu,
komend i symulacji. Ekrany można przygotować po ustaleniu kontraktu odczytu,
ale integracja wymaga działającego stanu i symulacji. Szczegóły generatora mapy,
jej wymiarów, ruchu na tick, kolizji, progów uszkodzeń, końca gry oraz dostępnego
na hostingu schedulera pozostają do decyzji w etapie 2.

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