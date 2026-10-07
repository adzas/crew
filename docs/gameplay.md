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
| Gospodarz | Zarządza pokojem i uruchamia rozgrywkę; w trakcie gry może zmieniać stanowiska załogi niezależnie od własnej roli | Tak |
| Kapitan | Widzi całą mapę i komunikuje się z załogą poza aplikacją | Tak |
| Sternik | Wprowadza polecenia dotyczące kursu statku | Tak |
| Lokalizator | W przyszłości wykrywa obiekty lub zagrożenia | Nie |
| Bocianie gniazdo | W przyszłości obserwuje otoczenie | Nie |
| Działonowy | W przyszłości obsługuje uzbrojenie | Nie |
| Operator prędkości | W przyszłości steruje prędkością | Nie; statek ma stałą prędkość |

„Gospodarz” jest uprawnieniem osoby, która pierwsza otworzy pokój, nie
stanowiskiem załogi. Zachowuje panel zarządzania stanowiskami także wtedy, gdy
sam obejmie rolę Sternika. Tylko gracz z rolą Kapitana widzi pełną mapę; widok
mapy jest jego umiejętnością i nie jest wyświetlany Sternikowi ani pozostałej
załodze. Ekran główny to osobny widok, który można wyświetlić na telewizorze
albo telefonie.

## Czas i polecenia

- Cooldown sterowania sternika wynosi 15 sekund, a świat wykonuje krok co 20
  sekund. Oba czasy są mierzone przez serwer.
- Polecenie sternika ustawia nowy kierunek dziobu. Statek wykonuje jeden ruch
  naprzód w tym kierunku podczas najbliższego kroku; jeśli przed krokiem
  nadejdzie kolejne dozwolone polecenie, zastępuje poprzednie. Ostatni ustawiony
  kurs pozostaje aktywny w kolejnych krokach, dopóki sternik go nie zmieni.
- Polecenia innych, niezależnych ról powinny łączyć się, a nie wzajemnie
  zastępować. MVP zakłada jedną osobę na stanowisko.
- Serwer jest źródłem czasu i stanu. Odświeżenie przeglądarki, zmiana karty lub
  utrata połączenia nie kończą ani nie resetują partii.

## Serie rozgrywek i podsumowania

- Seria składa się z trzech kolejnych partii. Gospodarz ręcznie uruchamia
  następną partię po obejrzeniu podsumowania poprzedniej.
- Każda partia ma własną mapę, stan początkowy, komendy, ticki i wynik. Nowa
  partia nie nadpisuje ani nie usuwa danych poprzedniej.
- Podsumowanie partii pokazuje wynik, liczbę udanych ruchów oraz minimalną
  liczbę legalnych ruchów pozostałych do celu, uwzględniając przeszkody i
  kierunek dziobu. Ruch po skosie przy narożniku jest dozwolony, jeśli pole
  docelowe jest legalne.
- Po trzeciej partii seria zostaje zamknięta i pokazuje wyniki wszystkich
  partii oraz łączną liczbę udanych ruchów. Porażka kończy partię, nie serię.
- Reset oznacza utworzenie następnej partii z nowym stanem, nigdy kasowanie
  historii.

## Wersja ustalona MVP: mapa, ruch i kolizje

### 1. Mapa

- Mapa ma rozmiar 20x20 komórek i jest stała w ramach jednej partii.
- Statek startuje wewnątrz obszaru gry, nie przy granicy.
- Każda partia ma własną, losową lub ręcznie ustaloną konfigurację przeszkód.
- Granica mapy jest nieprzekraczalna; wejście w obramowanie kończy grę.
- Przeszkody na mapie są trwałe i traktowane jak ściana.

### 2. Ruch statku

- Ruch odbywa się po siatce w ośmiu kierunkach.
- Statek ma kierunek dziobu, który może wskazywać na jedną z ośmiu głównych
  lub pośrednich kierunków: N, NE, E, SE, S, SW, W, NW.
- W każdej turze można wykonać tylko jeden ruch w przód względem dziobu.
- Żaden ruch nie może być wykonany w tył, bocznie ani na skręcie w miejscu.
- Kierunek jest rozstrzygany wyłącznie względem dziobu statku, nie względem
  ekranu gracza lub globalnych osi.
- Start jest dozwolony dopiero po obsadzeniu stanowisk Kapitana i Sternika.
- Po uruchomieniu rozgrywki serwer aktualizuje pozycję statku w krokach co 20
  sekund, a każde polecenie podane w oknie cooldownu zastępuje wcześniejsze.

### 3. Kolizje i koniec gry

- Kolizja z krawędzią mapy kończy grę natychmiast.
- Kolizja z przeszkodą lub nielegalnym polem kończy grę natychmiast.
- Brak osobnego systemu obrażeń w prostym MVP: uszkodzenie nie jest odraczane,
  nie liczone w przedziałach i nie ma osobnej taryfy "połowicznego przeżycia".
- W przypadku kolizji statek jest uznawany za zniszczony, a dalsze polecenia są
  odrzucane.
- Jeśli statek dotrze do celu, gra kończy się zwycięstwem.

### 4. Cel i warunki zwycięstwa

- Celem rozgrywki jest dotarcie statku do określonego punktu na mapie.
- Punkt docelowy jest ustalany na początku partii i jest stały dla tej rozgrywki.
- Zwycięstwo rozstrzygane jest po wejściu statku na pole docelowe zgodnie z
  prawidłowym ruchem w przód względem dziobu.
- Osiągnięcie celu kończy grę niezależnie od pozostałego czasu.

### 5. Cooldown, liczenie czasu i reset pokoju

- Cooldown polecenia wynosi 15 sekund.
- Serwer przyjmuje kolejny rozkaz dopiero po upływie tego okna; wcześniejsze
  polecenie nie jest wykonywane, a nowe zastępuje poprzednie, jeśli dotyczy tej
  samej czynności.
- Czas gry liczy się od momentu startu partii na serwerze, nie od zegara klienta.
- Czas odświeżania może być różny w przeglądarkach, ale stan gry jest jednoznaczny
  tylko po stronie serwera.
- Po godzinie rzeczywistej bezczynności pokój jest resetowany i może zostać
  uruchomiony ponownie od nowa.

## Minimalne ekrany

- Lobby: kod/link pokoju, lista graczy i zajęte/wolne role, przycisk startu dla
  gospodarza.
- Ekran główny: widok statku i lokalnego otoczenia.
- Panel kapitana: pełna mapa, dostępna wyłącznie dla gracza z rolą Kapitana.
- Panel sternika: polecenia sterowania statkiem.
- Panel gospodarza: zarządzanie rolami w trakcie rozgrywki, niezależne od roli
  załogowej gospodarza.

Lobby i wybór ról są zaimplementowane. Ekran główny rozgrywki, pełna mapa,
sterowanie statkiem i synchronizacja kroków symulacji pozostają na kolejny etap.
Niniejszy dokument opisuje docelowy zakres, a nie wyłącznie aktualny stan
funkcji.

## Etapy wdrożenia

Implementacja jest prowadzona etapami opisanymi w
[planie wdrożenia](development-and-deployment.md#plan-wdrożenia-rozgrywki).
Przed zmianami mechaniki obowiązuje etap stabilizacji lokalnych testów.