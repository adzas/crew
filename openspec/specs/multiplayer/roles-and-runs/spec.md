# Role i partie

## Wymagania

### Requirement: Start gry wymaga Kapitana i Sternika

Gra MUST rozpocząć się dopiero, gdy w pokoju przypisano aktywne role Kapitana
i Sternika.

#### Scenario: Brakuje wymaganej roli

- **WHEN** gospodarz próbuje uruchomić grę bez Kapitana lub Sternika
- **THEN** start jest odrzucany i partia nie zostaje utworzona

### Requirement: Role ograniczają widoczność informacji

Kapitan MUST otrzymywać pełną mapę bieżącej partii. Sternik MUST otrzymywać
lokalny podgląd 5×5 i interfejs ustawiania kursu.

#### Scenario: Kapitan otwiera grę

- **WHEN** gracz przegląda grę jako Kapitan
- **THEN** widzi pełną mapę partii

#### Scenario: Sternik otwiera grę

- **WHEN** gracz przegląda grę jako Sternik
- **THEN** widzi lokalny podgląd 5×5, aktualny kierunek dziobu i panel kursu

### Requirement: Historia zakończonych partii jest zachowana

Każda partia MUST zachować własną mapę, stan i historię. Rozpoczęcie kolejnej
partii MUST tworzyć nowy rekord zamiast nadpisywać poprzednią.

#### Scenario: Gospodarz rozpoczyna kolejną partię

- **WHEN** zakończona partia zostaje zastąpiona kolejną
- **THEN** poprzednia partia i jej dane pozostają zapisane
