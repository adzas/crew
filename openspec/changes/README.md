# Propozycje zmian

Każdą istotną zmianę zachowania lub architektury opisuj w osobnym katalogu:

```text
openspec/changes/<nazwa-zmiany>/
├── proposal.md
├── design.md        # tylko gdy trzeba uzasadnić decyzje lub kompromisy
├── tasks.md
└── specs/
    └── <nazwa-zdolności>/spec.md
```

Propozycja przedstawia problem i zakres; `specs/` zawiera wymagane zmiany
zachowania w formacie OpenSpec (`## ADDED Requirements`,
`## MODIFIED Requirements`, `## REMOVED Requirements`). Nazwa katalogu w
`specs/` odpowiada zdolności, której dotyczą wymagania, i pozwala powiązać je
z dokumentacją bazową. `design.md` uzasadnia decyzje, które nie wynikają
wprost z wymagań, a `tasks.md` dzieli wdrożenie na sprawdzalne kroki. Szablony
dla Obsidian są w `openspec/tpl/`; są pomocą przy pisaniu specyfikacji, nie
zastępują struktury ani nagłówków OpenSpec.

Drobne zmiany techniczne bez zmiany zachowania mogą być realizowane bez
katalogu propozycji. Nie umieszczaj w jednej zmianie wymagań, których zakres
nie został ustalony; zapisz je jako non-goals lub osobną przyszłą zmianę.

Po wdrożeniu przenieś zatwierdzone wymagania do odpowiednich plików
`openspec/specs/` i zachowaj ukończoną propozycję w
`openspec/changes/archive/`. Nie archiwizuj propozycji, która nie została
wdrożona ani świadomie zamknięta.
