# Plan rozbudowy multiplayer: role, widoczność i raportowanie

Status `[x]` oznacza w sekcji decyzji uzgodnione założenie, a w sekcjach
implementacji wyłącznie funkcję potwierdzoną w kodzie. Zatwierdzenie projektu
nie oznacza jeszcze wdrożenia mechaniki.

## Cel ogólny

- [x] Utrzymać obecny model serwerowo-autorytatywny i tickowy bez przenoszenia symulacji na klienta.
- [ ] Rozszerzyć grę do 4–6 graczy bez rozbijania istniejącej architektury pokoju, ról i rund.
- [x] Oprzeć współpracę na silnie asymetrycznej informacji i rozmowie załogi, bez głosowania w grze.
- [x] Przyjąć chaos i współpracę za cel projektowy; ograniczona widoczność mapy pozostaje do wdrożenia.
- [ ] Zapewnić pierwszy etap rozbudowy w zakresie 2–4 aktywnych ról, bez nadmiernego zwiększania złożoności produkcyjnej.

## Założenia produktu

- [x] Minimalny start gry: 2 aktywne role, obecnie Kapitan i Sternik.
- [x] Docelowa załoga: 4–6 graczy; aktualnie aplikacja nie egzekwuje limitu uczestników.
- [x] Akcje niezależnych ról nie powinny wzajemnie zastępować swoich efektów.
- [ ] Każda rola ma własny kontrakt: dostępne dane, czas wykonania, wynik i zapis raportu.
- [x] Informacje mają być silnie asymetryczne, ograniczone do potrzeb danej roli.
- [x] Współpraca odbywa się w rozmowie między graczami; aplikacja nie wprowadza formalnego głosowania.
- [x] Pierwsza rozbudowa obejmuje Lokalizatora i Bocianie gniazdo; Działonowy i Operator prędkości są poza jej zakresem.

## Zakres pierwszej rozbudowy

- [x] Docelowo Kapitan pozostaje głównym dowódcą i widzi mapę w ograniczonej, niepełnej formie.
- [x] Sternik pozostaje odpowiedzialny za kurs; serwer waliduje polecenie i wykonuje ruch.
- [x] Lokalizator ma skanować mały sektor 3×3.
- [x] Bocianie gniazdo ma obserwować obszar przed dziobem statku.
- [x] Raporty mają pozostawać w historii rundy i dawać ograniczoną informację, nie pełną mapę.

## Fundamenty już wdrożone

- [x] Serwer przechowuje stan partii i rozlicza ticki co 20 sekund; odczyt gry i zapis komendy nadrabiają należne ticki.
- [x] Polecenie Sternika jest walidowane i autoryzowane po stronie serwera, z cooldownem 15 sekund i historią komend zastąpionych.
- [x] Trwałe serie, partie, snapshoty stanu, komendy i ticki są rozdzielone w bazie.
- [x] Lobby obsługuje Kapitana i Sternika; rozpoczęcie gry wymaga obsadzenia obu ról.
- [x] Kapitan ma obecnie pełną mapę, a Sternik lokalny podgląd 5×5 i panel kursu.
- [x] Widok gry pokazuje neutralny znacznik X i odświeża się względem czasu następnego ticka.
- [x] Gospodarz może zmieniać role podczas gry; jego uprawnienie pozostaje niezależne od roli załogowej.

**Ważne:** powyższe elementy są fundamentem. Lokalizator, Bocianie gniazdo,
ich raporty oraz niepełna mapa Kapitana nie są jeszcze zaimplementowane.

## Plan wdrożenia na poziomie ogólnym

- [x] 1. Ustalenie kierunku ról i współpracy
  - [x] Zatwierdzić zakres ról: Kapitan, Sternik, Lokalizator, Bocianie gniazdo.
  - [x] Ustalić minimalny start (Kapitan + Sternik); role informacyjne są opcjonalne.
  - [ ] Doprecyzować kompletny kontrakt danych, czasu i raportu dla każdej nowej roli.
  - [ ] Ustalić, że każda rola posiada własny, odrębny panel akcji, ale nie ma własnego niezależnego ruchu statku.

- [ ] 2. Rozszerzenie modelu danych
  - [ ] Dodać do modelu gry pola/relacje dla ról dodatkowych.
  - [ ] Zaimplementować zapis raportów skanowania i obserwacji w tabeli/encji historii akcji.
  - [ ] Podłączyć serwer do zapisania wyników raportu w kontekście konkretnej rundy i ticka.

- [ ] 3. Rozszerzenie widoczności mapy
  - [ ] Zdefiniować, że Kapitan nie widzi pełnej mapy przeszkód.
  - [ ] Zapewnić widok częściowo ukrytej mapy, z informacją tylko o ogólnych obszarach, nie pełnym układzie wszystkich przeszkód.
  - [ ] Dodać narzędzie prezentacji z rozróżnieniem „widocznych”, „zidentyfikowanych” i „niezbadanych” obszarów.

- [ ] 4. Wdrożenie obserwacji Lokalizatora
  - [ ] Dodać mechanikę skanu 3×3 wokół statku lub wokół wybranego sektora.
  - [ ] Zdefiniować, że wynik skanu pokazuje przeszkody jako małe skały wystające z wody.
  - [ ] Ustalić, że lokalizator nie ujawnia całości mapy, tylko punktowy lub sektorowy obraz otoczenia.
  - [ ] Zarejestrować wynik skanu jako raport w historii rundy, z timestampem i opisem obszaru.

- [ ] 5. Wdrożenie obserwacji Bocianiego gniazda
  - [ ] Dodać mechanikę obserwacji wybranego pasa przed dziobem statku.
  - [ ] Ustalić, że raport bocianiego gniazda opisuje głównie zagrożenia podwodne i niebezpieczne obszary w przodzie statku.
  - [ ] Zdefiniować, że raport bocianiego gniazda ma być przedstawiany jako podwodne skały, które mogą być zaznaczone innym, ciemno-niebieskim kolorem.
  - [ ] Zapisać wynik obserwacji jako osobny typ raportu z odrębnym stylem wizualnym i odrębną semantyką.

- [ ] 6. Integracja z widokiem gry
  - [ ] Dodać warstwę prezentacji dla różnych typów danych wejściowych: pełna mapa Kapitana, lokalny obraz Sternika, raport Lokalizatora, raport Bocianiego gniazda.
  - [ ] Utrzymać czytelny kontrast między typami obiektów: normalna trasa, przeszkody, skały, reporty podwodne i odczyty z sektora.
  - [x] Utrzymać serwer jako źródło prawdy; frontend jedynie renderuje przekazane dane.

- [ ] 7. Testowanie i stabilizacja
  - [ ] Dodać testy dla logiki lokalizatora i raportów bocianiego gniazda.
  - [ ] Dodać testy dla reguł widoczności mapy i kryteriów wyświetlania.
  - [ ] Walidować zgodność z istniejącym tickowym harmonogramem i cooldownem komend.
  - [ ] Sprawdzić, że brak pełnej mapy nie wprowadza błędów w serwerowej logice ruchu.

## Plan wdrożenia na poziomie szczegółowym

### 1. Rola Lokalizator

- [ ] Dodać do lobby i widoku pokoju wybór roli Lokalizator.
- [ ] Dodać przycisk lub formularz akcji skanu sektora.
- [ ] Zdefiniować zakres skanu: 3×3 lub podobny mały obszar wokół statku.
- [ ] Dodać limit lub cooldown dla akcji skanu zgodny z tickiem gry.
- [ ] W modelu stanu zapisować wynik skanu w formie pozycji przeszkód lub obszarów niebezpiecznych.
- [ ] Dodać historię raportów z skanów do panelu lokalizatora oraz do dostępnego kontekstu dla Kapitana lub załogi.

### 2. Rola Bocianie gniazdo

- [ ] Dodać do lobby i widoku pokoju wybór roli Bocianie gniazdo.
- [ ] Dodać akcję obserwacji sektora przed dziobem statku.
- [ ] Ustalić granice obserwacji: obszar w kierunku dziobu, z uwzględnieniem głównej osi i ewentualnych zakrętów.
- [ ] Zdefiniować, że wynik obserwacji jest odczytem zagrożeń podwodnych, a nie pełnym obrazem mapy.
- [x] Przyjąć wyróżnienie wizualne: podwodne skały z raportu bocianiego gniazda mogą mieć ciemno-niebieski kolor.
- [ ] Przenieść wynik obserwacji do historii rundy z informacją o zakresie, kierunku i czasie wykonania.

### 3. Widoczność mapy Kapitana

- [ ] Ustalić, że Kapitan nadal widzi podstawowy obraz statku i jego przybliżone otoczenie.
- [ ] Ograniczyć widok mapy do obszarów rozpoznanych, ważnych dla decyzji strategicznych.
- [ ] Zabezpieczyć, aby pełne przeszkody nie były ujawniane bez odpowiedniego raportu z ról.
- [ ] Dodać mechanikę warstw: mapa bazowa, dane od ról, raporty z historii.
- [ ] Upewnić się, że Kapitan ma widok wspomagający decyzję, a nie pełną „cheat mapę”.

### 4. Prezentacja wizualna obiektów

- [x] Dla przeszkód lokalizatora przyjąć opis „mniejszych skał wystających z wody”.
- [x] Dla raportu bocianiego gniazda przyjąć opis „podwodnych skał”.
- [ ] Nadać odrębny kolor dla podwodnych skał z raportu bocianiego gniazda: ciemny, ciemno-niebieski odcień.
- [ ] Dla skał lokalizatora użyć jaśniejszego, pływającego odcienia, który nie miesza się z podwodnym raportem.
- [ ] Dodać legendę lub opisy w panelu gry, aby gracze rozpoznawali typy obiektów poprawnie.

### 5. Logika i bezpieczeństwo serwera

- [ ] Ograniczyć wykonanie akcji role-specific do ról z właściwymi uprawnieniami.
- [ ] Upewnić się, że raporty skanów i obserwacji są odrzucane, jeśli nie są legalne w ramach rundy lub ticka.
- [ ] Zabezpieczyć, aby żadne podania z frontendu nie mogły zmylić stanu serwera ani odczytu mapy.
- [ ] Utrzymać role oraz ich przydziały w spójności z modelem pokoju i listą aktywnych graczy.

### 6. Testy targetowe

- [ ] Dodać test jednostkowy dla logiki aktualizacji informacji po skanie Lokalizatora.
- [ ] Dodać test dla raportu Bocianiego gniazda, sprawdzający, że obszary podwodne są odnotowane w historii.
- [ ] Dodać test procesu widoczności: Kapitan nie dostaje pełnej mapy przeszkód bez odpowiednich raportów.
- [ ] Dodać test dla reguły, że raporty są zapisane razem z tickiem, rolą i pozycją statku.
- [ ] Dodać test dla składni i renderowania widoku z dodatkowymi warstwami mapy i kolorami obiektów.

## Kryteria akceptacji pierwszego etapu

- [ ] W pokoju można dodać role Lokalizator i Bocianie gniazdo bez naruszenia istniejącego działania Kapitana i Sternika.
- [ ] Skan Lokalizatora jest czytelny i rozumiany jako małe skały wystające z wody.
- [ ] Raport Bocianiego gniazda jest czytelny i rozumiany jako podwodne skały, oznaczone ciemno-niebieskim kolorem.
- [ ] Kapitan nie otrzymuje pełnego obrazka przeszkód, tylko rozszerzoną, niepełną informację strategiczną.
- [ ] Informacje z raportów są trwałe i zapisane w historii rundy.
- [ ] Gra pozostaje stabilna w tickowym modelu i nie narusza podstawowych zasad rozgrywki.

## Kolejność wdrożenia

- [ ] Krok 1: dodanie ról i kontraktów danych
- [ ] Krok 2: mechanika skanu Lokalizatora
- [ ] Krok 3: mechanika obserwacji Bocianiego gniazda
- [ ] Krok 4: widoczność mapy Kapitana i warstwy raportów
- [ ] Krok 5: testy i poprawki UX/UI
- [ ] Krok 6: weryfikacja pełnej sekwencji gry w rundzie testowej

## Notka projektowa

- [ ] Mechanika ma wspierać chaos, nie go usuwać.
- [ ] Każdy raport powinien pozostawać „małym oknem informacji”, a nie pełną, globalną mapą.
- [ ] Różnice wizualne mają pomagać graczom rozpoznawać źródło informacji bez potrzeby czytania długich opisów.
- [ ] W pierwszym etapie nie wprowadzamy jeszcze nowych mechanik walki, prędkości ani formalnych decyzji grupowych.

To jest plan implementacji pierwszej fazy rozbudowy multiplayer: ma być prosty, czytelny i zgodny z aktualną architekturą tickową oraz z zasadą silnej asymetrii informacji.
