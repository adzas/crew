# Zasady gry i zakres MVP

## Pętla rozgrywki

1. Gospodarz otwiera pokój i udostępnia znajomym link lub kod.
2. Gracze dołączają i zajmują wolne stanowiska.
3. Gospodarz uruchamia grę.
4. Załoga przekazuje polecenia. Świat aktualizuje się co 20 sekund.
5. Statek dociera do celu albo przegrywa po kolizji z granicą lub przeszkodą.

## Role planowane

| Rola | Informacje i wpływ | Zakres MVP |
| --- | --- | --- |
| Gospodarz | Zarządza pokojem i uruchamia rozgrywkę; w trakcie gry może zmieniać stanowiska załogi niezależnie od własnej roli | Tak |
| Kapitan | Koordynuje załogę; obecnie widzi całą mapę, docelowo mapę niepełną | Tak |
| Sternik | Wprowadza polecenia dotyczące kursu statku | Tak |
| Lokalizator | Docelowo skanuje sektor 3×3; raportuje mniejsze skały wystające z wody | Nie |
| Bocianie gniazdo | Docelowo obserwuje sektor przed dziobem; raportuje podwodne skały | Nie |
| Działonowy | W przyszłości obsługuje uzbrojenie | Nie |
| Operator prędkości | W przyszłości steruje prędkością | Nie; statek ma stałą prędkość |

„Gospodarz” jest uprawnieniem osoby, która pierwsza otworzy pokój, a nie
stanowiskiem załogi. Może zarządzać stanowiskami także wtedy, gdy sam obejmie
rolę załogową. Start wymaga obsadzenia Kapitana i Sternika; pozostałe role są
opcjonalne. W aktualnej wersji Kapitan widzi pełną mapę 20×20, a Sternik
otrzymuje lokalny podgląd 5×5. Docelowo mapa Kapitana będzie niepełna, a raporty
Lokalizatora i Bocianiego gniazda będą dostarczać ograniczone informacje.
Ekran rozgrywki można wyświetlić na telefonie lub większym ekranie.

## Czas i polecenia

- Cooldown sterowania sternika wynosi 15 sekund, a świat wykonuje krok co 20
  sekund. Oba czasy są mierzone przez serwer.
- Sternik może utrzymać kurs albo zmienić go o 45° w lewo lub w prawo względem
  aktualnego dziobu. Statek wykonuje jeden ruch naprzód podczas najbliższego
  kroku.
- Kolejne przyjęte polecenie Sternika przed tickiem zastępuje jego wcześniejsze
  oczekujące polecenie; ostatni ustawiony kurs pozostaje aktywny w następnych
  tickach.
- Docelowe akcje Lokalizatora i Bocianiego gniazda mają być niezależne od
  polecenia kursu. Nie są jeszcze zaimplementowane.
- Serwer jest źródłem czasu i stanu. Odświeżenie przeglądarki, zmiana karty lub
  utrata połączenia nie kończą ani nie resetują partii.

## Serie rozgrywek i podsumowania

- Seria składa się z trzech kolejnych partii. Gospodarz ręcznie uruchamia
  następną partię po obejrzeniu podsumowania poprzedniej.
- Każda partia ma własną mapę, stan początkowy, komendy, ticki i wynik. Nowa
  partia nie nadpisuje ani nie usuwa danych poprzedniej.
- Widok wyniku partii pokazuje rezultat i liczbę ruchów. Wyliczenie oraz
  prezentacja minimalnej trasy do celu nie są jeszcze dostępne w podsumowaniu.
- Po zakończeniu partii gospodarz może uruchomić kolejną. Dane partii są
  zachowane. Seria obejmuje maksymalnie trzy partie; podsumowanie zbiorcze
  serii pozostaje do wdrożenia.
- Reset oznacza utworzenie następnej partii z nowym stanem, nigdy kasowanie
  historii.

## Wersja ustalona MVP: mapa, ruch i kolizje

### 1. Mapa

- Mapa ma rozmiar 20x20 komórek i jest stała w ramach jednej partii.
- Statek startuje na ustalonej pozycji wewnątrz mapy, a cel i przeszkody są
  generowane dla partii z zapisanego seeda.
- Każda partia ma własną, losową lub ręcznie ustaloną konfigurację przeszkód.
- Granica mapy jest nieprzekraczalna; wejście w obramowanie kończy grę.
- Przeszkody na mapie są trwałe i traktowane jak ściana.

### 2. Ruch statku

- Ruch odbywa się po siatce w ośmiu kierunkach.
- Statek ma kierunek dziobu, który może wskazywać na jedną z ośmiu głównych
  lub pośrednich kierunków: N, NE, E, SE, S, SW, W, NW.
- W każdym ticku statek wykonuje jeden ruch naprzód względem utrzymanego kursu
  albo nowego kursu zaakceptowanego dla tego ticka.
- Polecenie pozwala utrzymać kierunek lub skręcić o jeden sąsiedni kierunek
  (45°); nie można wykonać ruchu w tył ani skręcić w miejscu.
- Kierunek jest rozstrzygany wyłącznie względem dziobu statku, nie względem
  ekranu gracza lub globalnych osi.
- Start jest dozwolony dopiero po obsadzeniu stanowisk Kapitana i Sternika.
- Po uruchomieniu rozgrywki serwer aktualizuje pozycję statku w krokach co 20
  sekund, a każde polecenie podane w oknie cooldownu zastępuje wcześniejsze.

### 3. Kolizje i koniec gry

- Kolizja z krawędzią mapy kończy grę natychmiast.
- Kolizja z przeszkodą kończy partię; wyjście poza mapę również ją kończy.
- Brak osobnego systemu obrażeń w prostym MVP: uszkodzenie nie jest odraczane,
  nie liczone w przedziałach i nie ma osobnej taryfy "połowicznego przeżycia".
- Po zakończeniu partii dalsze polecenia nie są przyjmowane.
- Uwaga implementacyjna: przy kolizji z przeszkodą końcowy snapshot zapisuje
  pozycję próby ruchu na polu przeszkody; przy wyjściu poza granicę zachowuje
  poprzednią pozycję. Oba przypadki kończą partię porażką.
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
- Automatyczny reset pokoju po godzinie bezczynności jest planowany, ale jeszcze
  niezaimplementowany.

## Minimalne ekrany

- Lobby: kod/link pokoju, lista graczy i zajęte/wolne role, przycisk startu dla
  gospodarza.
- Ekran główny: widok statku i lokalnego otoczenia.
- Panel kapitana: obecnie pełna mapa, dostępna wyłącznie dla Kapitana; docelowo
  mapa niepełna, uzupełniana raportami załogi.
- Panel sternika: polecenia sterowania statkiem.
- Panele Lokalizatora i Bocianiego gniazda: planowane; skan 3×3 i obserwacja
  przed dziobem.
- Panel gospodarza: zarządzanie rolami w trakcie rozgrywki, niezależne od roli
  załogowej gospodarza.

Lobby, ekran rozgrywki, mapa, sterowanie i synchronizacja ticków są
zaimplementowane. Aktualny stan i plan kolejnych prac są rozdzielone w
[planie rozbudowy multiplayer](multiplayer-role-expansion-plan.md).

## Etapy wdrożenia

Implementacja jest prowadzona etapami opisanymi w
[planie wdrożenia](development-and-deployment.md#plan-wdrożenia-rozgrywki).
Przed zmianami mechaniki obowiązuje etap stabilizacji lokalnych testów.