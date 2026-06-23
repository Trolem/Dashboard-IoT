# Panel monitorowania zuzycia energii dla systemu IoT
## Karta opisu projektu

**Przedmiot:** Zaawansowane aplikacje internetowe  
**Typ aplikacji:** Single Page Application z komunikacja przez REST API  
**Temat:** Panel webowy do prezentacji pomiarow energii elektrycznej z systemu IoT  

Projekt przedstawia nowoczesny dashboard webowy przeznaczony do monitorowania
parametrow energii elektrycznej. Aplikacja prezentuje najnowszy pomiar, podsumowanie
zbioru danych, wykresy, porownanie urzadzen oraz tabele rekordow pomiarowych. Dane sa
pobierane asynchronicznie z endpointow PHP zwracajacych odpowiedzi w formacie JSON.
## Funkcje systemu

- prezentacja najnowszego pomiaru napiecia, natezenia, zuzycia energii,
  czestotliwosci, mocy czynnej, wspolczynnika mocy i mocy pozornej,
- wybor parametrow widocznych na kafelkach i wykresie,
- kafelki podsumowania: liczba pomiarow, zakres dat, energia laczna i maksymalna moc,
- automatyczne odswiezanie danych przez Fetch API,
- wykres historii wybranych parametrow z uzyciem Chart.js,
- wykres porownania zuzycia energii przez urzadzenia,
- tabela ostatnich rekordow pomiarowych z filtrem daty,
- eksport rekordow pomiarowych do pliku CSV,
- symulacja sterowania przekaznikiem zasilania urzadzenia,
- osobna strona emulatora ESP32 odczytujaca komende z API,
- drugi wykres do analizy danych demonstracyjnych z wybranego dnia,
- przykladowe scenariusze pomiarowe, np. czajnik, telewizor, stanowisko komputerowe i pralka,
- rozdzielenie warstwy frontendowej od backendowej,
- tryb danych testowych oraz przygotowanie pod przyszle polaczenie z MySQL.

## Uruchomienie lokalne

Do uruchomienia obecnej wersji projektu wymagany jest PHP 8.0 lub nowszy.
Projekt nie wymaga XAMPP ani bazy MySQL, poniewaz korzysta z danych demonstracyjnych.

### Windows

1. Sprawdz, czy PHP jest dostepne:

```text
php -v
```

2. Przejdz do katalogu projektu:

```text
cd EnergyLab-IoT-Dashboard
```

3. Uruchom lokalny serwer PHP:

```text
php -S 127.0.0.1:8080 -t .
```

4. Otworz w przegladarce:

```text
http://127.0.0.1:8080/
```

### Linux

W systemie Debian/Ubuntu PHP CLI mozna zainstalowac poleceniami:

```text
sudo apt update
sudo apt install php-cli
```

Nastepnie w katalogu projektu:

```text
php -S 127.0.0.1:8080 -t .
```

Adres aplikacji:

```text
http://127.0.0.1:8080/
```

Jesli port `8080` jest zajety, mozna uzyc np. `8081` albo `8082`.

## Stack technologiczny

- HTML5 - struktura aplikacji SPA,
- CSS3 - responsywny wyglad dashboardu,
- JavaScript - logika interfejsu, Fetch API i odswiezanie danych,
- Chart.js - wizualizacja danych na wykresie,
- PHP - endpointy REST API zwracajace JSON,
- wbudowany serwer PHP - lokalne uruchomienie projektu,
- dane demonstracyjne w PHP - symulacja pomiarow bez fizycznego ukladu,
- CSV - eksport rekordow pomiarowych do pliku.

## Struktura projektu

```text
.
+-- api/
|   +-- config.php
|   +-- control.php
|   +-- control_state.php
|   +-- daily.php
|   +-- demo_data.php
|   +-- export_csv.php
|   +-- history.php
|   +-- latest.php
|   +-- records.php
|   +-- summary.php
+-- assets/
|   +-- css/
|   |   +-- styles.css
|   +-- js/
|       +-- app.js
|       +-- emulator.js
+-- docs/
|   +-- dokumentacja-projektu.md
|   +-- kanban.md
+-- database/
|   +-- seed-demo.sql
+-- esp32-emulator.html
+-- index.html
+-- README.md
```
