# Gruppenschaltung  

Dieses Modul schaltet eine Gruppe von Geräten.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.  

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Schaltet eine Gruppe von Geräten aus oder an.

### 2. Voraussetzungen

* IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.  

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Gruppenschaltung'-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen).  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ------------------------------------------
Funktion        |
Wartungsmodus   | Wartungsmodus aus-, bzw. anschalten.
Gruppe          |
Variablen       | Variablen, welche geschaltet werden sollen

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name        | Typ       | Beschreibung
----------- | --------- | ------------------------
GroupSwitch | boolean   | Gruppenschalter (Aus/An)

#### Profile

Es werden keine neuen Profile angelegt.

### 6. WebFront

Gruppenschaltung (Aus/An)

### 7. PHP-Befehlsreferenz
```text
boolean GS_ToggleGroup(integer $InstanzID, boolean $Status);  
Schaltet eine Gruppe von Geräten aus, bzw. an.  
Konnte der Befehl erfolgreich ausgeführt werden, so ist der Rückwert true, andernfalls false. 

Beispiel:  
GS_ToggleGroup(12345, false);
```