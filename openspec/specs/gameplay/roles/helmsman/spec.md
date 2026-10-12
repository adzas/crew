# Rola sternika

## Wymagania

### Requirement: Sternik jest wymaganym członkiem załogi

#### Scenario: graczy jest trzech

- **WHEN** gracze wybrali role: kapitan, bocianie gniazdo, lokalizator
- **THEN** gra nie uruchamia się, ponieważ rola sternika jest wymagana w grze.

#### Scenario: graczy jest trzech

- **WHEN** gracze wybrali rolę kapitana, sternika i lokalizatora
- **THEN** gra uruchamia się.


