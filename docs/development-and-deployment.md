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
2. **Reguły MVP.** Ustalone: mapa 20x20, ruch tylko w przód względem dziobu
   w ośmiu kierunkach, ruch po skosie dozwolony, jeśli docelowe pole jest legalne,
   kolizja z granicą lub przeszkodą kończy partię, bez osobnego systemu obrażeń,
   start wymaga Kapitana i Sternika. Cooldown wynosi 15 sekund, tick 20 sekund,
   ostatni dozwolony kurs pozostaje aktywny; seria ma trzy ręcznie uruchamiane
   partie. Kryterium: sporne przypadki mechaniki opisują jednoznaczne testy.
3. **Start i trwały stan partii.** Dodać zapis serii z trzema partiami, mapy i
   początkowego stanu statku. Start dostępny tylko gospodarzowi, transakcyjny i
   dozwolony po obsadzeniu obu ról. Kolejne partie gospodarz uruchamia ręcznie
   po podsumowaniu. Kryterium: start tworzy dokładnie jedną serię/partię, a
   próby nieuprawnione, przedwczesne i powtórne nie duplikują stanu.
4. **Polecenia graczy.** Zapisywać polecenia niezależnie od ogólnego logu akcji;
   serwer sprawdza rolę, stan gry, poprawność danych i cooldown 15 sekund oraz
   zachowuje historię poleceń zastąpionych przed tickiem. Kryterium: testy
   poprawnych i odrzuconych poleceń oraz łączenia poleceń ról.
5. **Symulacja świata.** Wydzielić deterministyczny krok serwerowy co 20 sekund,
   zastosować polecenia i kurs, zapisać każdą pozycję, rozstrzygnąć kolizje i
   koniec gry oraz zabezpieczyć tick przed podwójnym wykonaniem. Kryterium:
   reguły domenowe mają testy brzegowe, a ponowiony tick nie dubluje skutków.
6. **Ekrany ról.** Podłączyć ekran główny, pełną mapę Kapitana i panel Sternika
   do trwałych projekcji stanu; klient wysyła komendy, lecz nie rozstrzyga
   symulacji. Wynik partii pokazuje udane ruchy i pozostałą legalną trasę.
   Kryterium: testy uprawnień i braku wycieku mapy, ręczny test dwóch kart.
7. **Koniec, seria i utrzymanie.** Blokować komendy po wyniku, zachować historię
   partii, pozwolić gospodarzowi uruchomić następną oraz po trzeciej pokazać
   podsumowanie serii. Dodać reset pokoju po godzinie bezczynności. Ticki
   nadrabia `advanceDue` wywoływane przy odczycie stanu i zapisie komendy,
   niezależnie od dostępności schedulera. Kryterium: pełny test lobby -> trzy
   partie -> podsumowanie i ponowne uruchomienie pokoju.

Etap 1 jest bramką dla całego rozwoju i został uzgodniony. Trwały stan blokuje
komendy i symulację. Ekrany można integrować po ustaleniu projekcji stanu;
klient nie rozstrzyga mechaniki. Generator mapy musi tworzyć osiągalny cel, a
`advanceDue` rozlicza opóźnione ticki według czasu serwera.

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