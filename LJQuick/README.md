# LJQuick
Das LJQuick-Module hilft dabei KNX Instanzen schnell und unkompliziert hinzuzufügen. 
Durch auswählen einer Gruppe und einem Klick auf den Button werden die Instanzen mit den richtigen Adressen erstellt. 
Datum und Uhrzeit wird an die Übergeordnete Instanz gesendet. 

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

* Über den Module Store das 'LJQuick'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen `https://github.com/symcon/SymconLJ`

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'LJQuick'-Modul mithilfe des Schnellfilters gefunden werden.  
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
Electricity.Wh         | integer
Electricity.kWh        | integer
Electricity.MWh        | integer
Volume.CubicMeter      | integer
Volume.Liter           | integer
Power.W                | float
Flow.CubicMeterPerHour | float

### 6. PHP-Befehlsreferenz

`boolean LJ_GenerateSwitch(integer $Group);`
`boolean LJ_GenerateDim(integer $Group);`
`boolean LJ_GenerateShutter(integer $Group);`
`boolean LJ_GenerateCounter(integer $Group, integer $Type);`
`boolean LJ_GenerateHeating(integer $Group, integer $Type);`

Generieren KNX-Instanzen nach Typ mit einer mitgegebenen Gruppe.

`boolean LJ_SendDateTime(integer $Group);`

Beispiel:
`LR_BeispielFunktion(12345);`
