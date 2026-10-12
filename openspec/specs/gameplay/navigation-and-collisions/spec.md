# Nawigacja i kolizje

## Wymagania

### Requirement: Ruch odbywa się w ośmiu kierunkach

Statek MUST poruszać się po siatce w jednym z kierunków N, NE, E, SE, S, SW,
W lub NW. W kroku symulacji statek wykonuje jeden ruch naprzód zgodnie z
utrzymanym kursem lub zaakceptowanym poleceniem.

#### Scenario: Statek utrzymuje kurs

- **WHEN** krok symulacji zostaje wykonany bez nowej komendy
- **THEN** statek porusza się o jedno pole zgodnie z zapisanym kursem

### Requirement: Sternik może korygować kurs o 45 stopni

Sternik MUST móc utrzymać bieżący kurs albo wybrać jeden z dwóch
sąsiadujących kierunków. Kurs jest liczony względem dziobu statku.

#### Scenario: Sternik zmienia kurs

- **WHEN** Sternik wybiera bieżący lub sąsiedni kierunek
- **THEN** zaakceptowana komenda określa kierunek ruchu w kroku symulacji

### Requirement: Kolizja kończy partię

Ruch poza mapę albo na pole przeszkody MUST zakończyć partię porażką.
Wejście na pole celu MUST zakończyć partię zwycięstwem.

#### Scenario: Statek uderza w przeszkodę lub granicę

- **WHEN** następny ruch statku prowadzi na przeszkodę albo poza mapę
- **THEN** partia kończy się porażką z odpowiednim powodem

#### Scenario: Statek dociera do celu

- **WHEN** następny prawidłowy ruch statku prowadzi na pole celu
- **THEN** partia kończy się zwycięstwem

### Requirement: Sternik widzi rzeczywisty kierunek dziobu

Widok Sternika MUST wskazywać kurs zapisany w bieżącym stanie statku, również
gdy zaakceptowana komenda oczekuje jeszcze na wykonanie w ticku.

#### Scenario: Oczekująca komenda nie zmienia przedwcześnie wskazania

- **WHEN** istnieje oczekująca komenda Sternika, ale symulacja nie wykonała
  jeszcze kroku
- **THEN** oznaczenie dziobu pokazuje bieżący kurs statku, a nie kurs oczekujący
