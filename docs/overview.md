# Crew: opis projektu

## Założenie

Crew to kooperacyjna gra przeglądarkowa dla znajomych. Załoga steruje jednym
pirackim statkiem, ale poszczególni gracze mają różne informacje i panele.
Zadaniem drużyny jest dopłynięcie do celu na skończonej mapie.

Gra ma działać w przeglądarce telefonu. Główny ekran, przeznaczony np. na
telewizor, pokazuje statek i jego najbliższe otoczenie. Gospodarz może otworzyć
ten ekran na telefonie lub większym wyświetlaczu.

## Ustalone decyzje

- Rozgrywka jest wspólna i asynchroniczna w krótkich krokach symulacji.
- Pokój nie wymaga konta ani hasła; wejście odbywa się przez link/kod pokoju i
  wybór wolnej roli.
- Pierwsza osoba otwierająca pokój zostaje gospodarzem i może uruchomić grę.
- Każda rozgrywka ma własną mapę, cel i stan statku; serwer zawsze jest
  źródłem prawdy o pozycji i kierunku.
- Role są rozdzielone: gospodarz zarządza pokojem, kapitan widzi pełną mapę,
  sternik podaje kurs, a serwer rozlicza ticki i kończy rundę.
- Seria składa się z trzech partii; po każdej rundzie host może uruchomić następną,
  a po trzeciej pokazuje się podsumowanie całej serii.
- Po godzinie bezczynności pokój i rozgrywka są resetowane.
- Statek ma stałą prędkość i nie ma osobnego systemu obrażeń w MVP.

## Zakres dokumentacji

Szczegółowy zakres pierwszej wersji jest w [zasadach gry](gameplay.md), a
przepływ komponentów pokazuje [Canvas](crew.canvas).