# Crew: opis projektu

## Założenie

Crew to kooperacyjna gra przeglądarkowa dla znajomych. Załoga steruje jednym
pirackim statkiem, ale poszczególni gracze mają różne informacje i panele.
Zadaniem drużyny jest dopłynięcie do celu na skończonej mapie.

Gra ma działać w przeglądarce telefonu. Główny ekran, przeznaczony np. na
telewizor, pokazuje statek i jego najbliższe otoczenie. Gospodarz może otworzyć
ten ekran na telefonie lub większym wyświetlaczu.

## Ustalone decyzje

- Rozgrywka jest kooperacyjna, a świat aktualizuje się w krótkich, serwerowych
  krokach symulacji.
- Pokój nie wymaga konta ani hasła; wejście odbywa się przez link/kod pokoju i
  wybór wolnej roli.
- Pierwsza osoba otwierająca pokój zostaje gospodarzem. Gospodarz uruchamia
  grę i może zmieniać role podczas rozgrywki.
- Każda rozgrywka ma własną mapę, cel i stan statku; serwer zawsze jest
  źródłem prawdy o pozycji i kierunku.
- Seria składa się z trzech partii; gospodarz może uruchomić następną po
  zakończeniu poprzedniej. Podsumowanie całej serii pozostaje do wdrożenia.
- Statek porusza się w tickach co 20 sekund. Polecenie Sternika ma cooldown
  15 sekund, oba czasy egzekwuje serwer.
- W aktualnej wersji Kapitan widzi pełną mapę, a Sternik lokalny podgląd 5×5
  i panel kursu. Docelowo Kapitan ma widzieć mapę niepełną, uzupełnianą
  raportami ról informacyjnych.
- Minimalny skład startowy to Kapitan i Sternik. Docelowa załoga liczy 4–6 osób;
  aplikacja nie egzekwuje jeszcze limitu uczestników.
- Po godzinie bezczynności pokój nie jest jeszcze automatycznie resetowany.
- Statek ma stałą prędkość; MVP nie zawiera osobnego systemu obrażeń.

## Stan funkcji

Lobby, role aktywne Kapitana i Sternika, serwerowy stan gry, generowanie map,
przyjmowanie poleceń, rozliczanie ticków, widoki ról i kolejne partie są
zaimplementowane. Lokalizator i Bocianie gniazdo są obecnie rolami nieaktywnymi;
skany, raporty i docelowa asymetria mapy nie są jeszcze dostępne.

## Zakres dokumentacji

Szczegółowy zakres pierwszej wersji jest w [zasadach gry](gameplay.md), a
decyzje techniczne opisuje [architektura](architecture.md). Kolejność i status
prac przedstawia [plan wdrożenia](development-and-deployment.md#plan-wdrozenia-rozgrywki),
a następny przyrost opisuje [plan rozbudowy multiplayer](multiplayer-role-expansion-plan.md).
Przepływ komponentów pokazuje [Canvas](crew.canvas).