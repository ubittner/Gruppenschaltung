# Gruppenschaltung

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Auslöser](#4-auslöser)
5. [Externe Aktion](#5-externe-aktion)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz)
   1. [Gruppenschaltung](#61-gruppenschaltung)
   2. [Statusaktualisierung](#62-statusaktualisierung)

### 1. Modulbeschreibung

Dieses Modul schaltet eine Gruppe von Variablen (Aus/An).

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                                   +--------------------------+
            Auslöser ------------->| Gruppenschaltung (Modul) |<------------- externe Aktion
                                   |                          |
                                   | Gruppenschaltung         |
                                   +------------+-------------+
                                                |
                                                |
                                                |    +------------+
                                                +--->| Variable 1 |
                                                |    +------------+
                                                |
                                                |    +------------+
                                                +--->| Variable 2 |
                                                |    +------------+
                                                |
                                                |    +------------+
                                                +--->| Variable n |
                                                     +------------+
```

### 4. Auslöser

Das Modul Gruppenschaltung kann auf verschiedene Auslöser reagieren.

### 5. Externe Aktion

Das Modul Gruppenschaltung kann über eine externe Aktion geschaltet werden.  
Nachfolgendes Beispiel schaltet die Gruppenschaltung an.

> GRPS_ToggleGroup(12345, true);

### 6. PHP-Befehlsreferenz

#### 6.1 Gruppenschaltung

```
boolean GRPS_ToggleGroup(integer INSTANCE_ID, boolean STATE);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis **TRUE**, andernfalls **FALSE**.

| Parameter     | Wert  | Bezeichnung    |
|---------------|-------|----------------|
| `INSTANCE_ID` |       | ID der Instanz |
| `STATE`       | false | Aus            |
|               | true  | An             |

Beispiel:
> $toggle = GRPS_ToggleGroup(12345, false);  
> echo json_encode($toggle);

---

#### 6.2 Statusaktualisierung

```
boolean GRPS_UpdateGroup(integer INSTANCE_ID);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis **TRUE**, andernfalls **FALSE**.

| Parameter     | Wert  | Beschreibung   |
|---------------|-------|----------------|
| `INSTANCE_ID` |       | ID der Instanz |

Beispiel:
> $update = GRPS_UpdateGroup(12345);  
> echo json_encode($update);

---