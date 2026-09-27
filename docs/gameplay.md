# Zasady gry i zakres MVP

## Pętla rozgrywki

1. Gospodarz otwiera pokój i udostępnia znajomym link lub kod.
2. Gracze dołączają i zajmują wolne stanowiska.
3. Gospodarz uruchamia grę.
4. Załoga przekazuje polecenia. Świat aktualizuje się co 20 sekund.
5. Statek dociera do celu albo zostaje zniszczony wskutek uszkodzeń.

## Role planowane

| Rola | Informacje i wpływ | Zakres MVP |
| --- | --- | --- |
| Gospodarz | Zarządza pokojem i uruchamia rozgrywkę; może pokazać główny ekran | Tak |
| Kapitan | Widzi całą mapę i komunikuje się z załogą poza aplikacją | Tak |
| Sternik | Wprowadza polecenia dotyczące kursu statku | Tak |
| Lokalizator | W przyszłości wykrywa obiekty lub zagrożenia | Nie |
| Bocianie gniazdo | W przyszłości obserwuje otoczenie | Nie |
| Działonowy | W przyszłości obsługuje uzbrojenie | Nie |
| Operator prędkości | W przyszłości steruje prędkością | Nie; statek ma stałą prędkość |

„Gospodarz” jest uprawnieniem osoby, która pierwsza otworzy pokój, nie
stanowiskiem załogi. Ekran główny to osobny widok, który można wyświetlić na
telewizorze albo telefonie.

## Czas i polecenia

- Gracz może wysłać kolejną akcję po 5 sekundach.
- Symulacja świata wykonuje krok co 20 sekund.
- Jeśli gracz zmieni polecenie przed krokiem świata, jego ostatnie polecenie
  zastępuje wcześniejsze polecenie dotyczące tej samej rzeczy.
- Różne role sterują różnymi elementami, więc ich polecenia powinny się łączyć.
- MVP zakłada jedną osobę na rolę.

Szczegóły ruchu statku, kierunki sterowania i sposób rozstrzygania kolizji
wymagają doprecyzowania przed implementacją mechaniki.

## Mapa, uszkodzenia i koniec gry

- Mapa jest skończona i inna dla każdej rozgrywki.
- Celem jest dotarcie statkiem do wskazanego miejsca.
- W MVP występują przeszkody i kolizje, ale nie ma walki ani strzelania.
- Statek ma trzy stopnie uszkodzeń; ich dokładne progi oraz warunek zniszczenia
  statku trzeba ustalić przy implementacji mechaniki.
- Po godzinie bezczynności pokój jest resetowany.

## Minimalne ekrany

- Lobby: kod/link pokoju, lista graczy i zajęte/wolne role, przycisk startu dla
  gospodarza.
- Ekran główny: widok statku i lokalnego otoczenia.
- Panel kapitana: pełna mapa.
- Panel sternika: polecenia sterowania statkiem.

Lobby i wybór ról są zaimplementowane. Ekran główny rozgrywki, pełna mapa,
sterowanie statkiem i synchronizacja kroków symulacji pozostają na kolejny etap.
Niniejszy dokument opisuje docelowy zakres, a nie wyłącznie aktualny stan
funkcji.