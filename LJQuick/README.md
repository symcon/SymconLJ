# LJQuick
Das KNX quick (Lingg&Janke) Modul hilft dabei die KNX quick Instanzen schnell und unkompliziert hinzuzufügen. 
Durch auswählen einer Gruppe und einem Klick auf den Button werden die Instanzen mit den richtigen Adressen erstellt. 
Datum und Uhrzeit werden alle 10 Minuten an die KNX Adresse 30/3/254 gesendet.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz)

### 1. Funktionsumfang

* Erstellung von Instanzen anhand von Gruppeneinstellungen

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0

### 3. Software-Installation

* Über den Module Store das 'KNX quick (Lingg&Janke)'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen `https://github.com/symcon/SymconLJ`

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'KNX quick (Lingg&Janke)'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                 | Beschreibung
-------------------- | ------------------
Switch               | Auswahl einer Gruppe für einen Switch
Generate             | Button, welcher die KNX Instanzen für Switch erstellt
Dimming              | Auswahl einer Gruppe für Dimming
Generate             | Button, welcher die KNX Instanzen für Dimming erstellt
Shutter              | Auswahl einer Gruppe für Shutter
Generate             | Button, welcher die KNX Instanzen für Shutter erstellt
Energy               | Auswahl einer Gruppe für Energiezähler
Generate             | Button, welcher die KNX Instanzen für Energie erstellt
Water/Gas/Oil        | Auswahl einer Gruppe für Wasser/Gas/Öl - Zähler
Generate             | Button, welcher die KNX Instanzen für Wasser/Gas/Öl erstellt
HeatQuantity         | Auswahl einer Gruppe für HeatQuantity - Zähler
Generate             | Button, welcher die KNX Instanzen für HeatQuantity erstellt
Temperature          | Auswahl einer Gruppe für Temperatur
Generate             | Button, welcher die KNX Instanzen für Temperatur erstellt
Temperature/Humidity | Auswahl einer Gruppe für Temperatur/Humidity

### 5. Statusvariablen

Die Statusvariablen/Kategorien/Profile werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Profile

Name                   | Typ
---------------------- |--------------
Electricity.Wh         | Integer
Electricity.kWh        | Integer
Electricity.MWh        | Integer
Volume.CubicMeter      | Integer
Volume.Liter           | Integer
Power.W                | Float
Flow.CubicMeterPerHour | Float

### 6. PHP-Befehlsreferenz

`boolean LJ_GenerateSwitch(integer $Group);`
`boolean LJ_GenerateDim(integer $Group);`
`boolean LJ_GenerateShutter(integer $Group);`
`boolean LJ_GenerateCounter(integer $Group, integer $Type);`
`boolean LJ_GenerateHeating(integer $Group, integer $Type);`
Generiert KNX-Instanzen nach Typ mit einer mitgegebenen Gruppe

Beispiel:
`LJ_GenerateSwitch(1);`

`boolean LJ_SendDateTime(integer $Group);`
Sendet das aktuelle Datum/Uhrzeit auf den KNX-Bus

Beispiel:
`LJ_SendDateTime(1);`