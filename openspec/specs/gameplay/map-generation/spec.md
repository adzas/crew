# Generowanie mapy

## Wymagania

### Requirement: Mapa partii jest deterministyczna względem seeda

Generator MUST zwracać ten sam rozmiar mapy, pozycję startową, cel i układ
przeszkód dla tego samego seeda.

#### Scenario: Ponowne wygenerowanie mapy

- **WHEN** generator otrzymuje seed użyty wcześniej
- **THEN** zwraca tę samą konfigurację mapy

### Requirement: Mapa zachowuje bezpieczny obszar startowy

Pole startowe MUST być wolne od przeszkód. Wszystkie pola w kwadracie 5×5
wyśrodkowanym na starcie MUST być wolne, zapewniając dwa pola odstępu
w każdym kierunku.

#### Scenario: Statek rozpoczyna partię

- **WHEN** generator umieszcza przeszkody na mapie
- **THEN** żadna przeszkoda nie znajduje się w odległości maksymalnie dwóch
  pól od startu w osi X i maksymalnie dwóch pól w osi Y

### Requirement: Mapa zawiera trasę do celu

Generator MUST zwracać mapę z trasą osiągalną ze startu i początkowym kursem
SE do celu. Jeśli wygenerowany układ nie spełnia tego warunku, generator
ponawia generowanie; po wyczerpaniu prób może zwrócić mapę bez przeszkód.

#### Scenario: Statek może dotrzeć do celu

- **WHEN** generator zwraca mapę
- **THEN** reguły ruchu mogą wyznaczyć trasę od startu i kursu SE do celu

### Requirement: Przeszkody są grupowane w wyspy

Generator MUST umieszczać przeszkody w zwartych skupiskach, zamiast polegać
wyłącznie na niezależnym losowaniu pojedynczych pól.

#### Scenario: Na mapie powstają skupiska

- **WHEN** generator tworzy mapę z przeszkodami
- **THEN** co najmniej część przeszkód sąsiaduje ze sobą, tworząc skupiska
