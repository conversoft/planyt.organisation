# Trello OAuth einmalig einrichten

Diese Anleitung ist **nur für den Administrator der Installation** gedacht. Mitarbeitende müssen keine Client-ID, kein Secret und keine Callback-URL kennen.

Nach dieser einmaligen Einrichtung sehen Mitarbeitende in Planyt nur noch den Button **„Trello verbinden“**.

## Was wird eingerichtet?

Planyt benötigt für Trello ausschließlich lesenden Zugriff:

- Mitglieder lesen: `read:member:trello`
- Boards, Listen und Karten lesen: `read:board:trello`
- Refresh-Token erhalten: `offline_access`

Planyt fordert **keine Trello-Schreibrechte** an.

## 1. Trello-App öffnen oder anlegen

1. Öffne die Trello-/Atlassian-App-Verwaltung.
2. Lege eine neue App für **Planyt Organisation** an oder wähle eine bestehende App.
3. Öffne in der App den Bereich **OAuth 2.0**.

Für Planyt verwenden wir einen **Confidential Client**, weil Planyt eine serverseitige PHP-Anwendung mit geschütztem Backend ist.

## 2. OAuth-2.0-Client konfigurieren

Im OAuth-2.0-Bereich:

1. Sicherheits-/Client-Typ **Confidential** wählen.
2. Als erlaubte Scopes aktivieren:
   - `read:member:trello`
   - `read:board:trello`
3. Keine `write:...`-Scopes aktivieren.

Hinweis: `offline_access` wird beim eigentlichen Login von Planyt zusätzlich angefordert, damit ein Refresh-Token ausgegeben werden kann.

## 3. Callback-URL hinterlegen

Als Callback-/Redirect-URL die Adresse von Planyt eintragen.

Für die lokale Entwicklung zum Beispiel:

```text
http://localhost:8080/oauth/trello/callback.php
```

Für eine produktive Installation entsprechend:

```text
https://DEIN-HOST/oauth/trello/callback.php
```

Die URL muss später exakt genauso in Planyt eingetragen werden.

## 4. Client-ID und Client-Secret kopieren

Nach dem Erstellen des Confidential Clients erhältst du:

- **Client-ID**
- **Client-Secret**

Diese Werte niemals ins Git-Repository schreiben und nicht an Mitarbeitende weitergeben.

## 5. Trello einmalig in Planyt freischalten

Im Projektverzeichnis auf dem Server bzw. Entwicklungsrechner ausführen:

```bash
php bin/planyt trello:configure
```

Planyt fragt nacheinander:

```text
Client-ID:
Client-Secret:
Callback-URL:
```

Für die lokale Entwicklung zum Beispiel:

```text
http://localhost:8080/oauth/trello/callback.php
```

Planyt speichert diese Installationskonfiguration geschützt unter `storage/system/config.json`. Sie wird nicht ins Repository eingecheckt.

## 6. Als Mitarbeiter verbinden

Danach ist für Mitarbeitende nur noch Folgendes nötig:

1. Planyt öffnen.
2. Unter **Verbindungen → Trello** auf **„Trello verbinden“** klicken.
3. Atlassian/Trello öffnet sich.
4. Falls das Trello-/Atlassian-Konto mit Google verknüpft ist, kann dort **Mit Google anmelden** verwendet werden.
5. Den lesenden Zugriff bestätigen.
6. Danach geht es automatisch zurück zu Planyt.

Planyt speichert das persönliche Trello-Token verschlüsselt in der jeweiligen Nutzerdatei `tokens.json`.

## 7. Boards auswählen

Nach erfolgreicher Trello-Verbindung:

1. **Jetzt synchronisieren** anklicken.
2. Unter **Verbindungen → Trello** erscheinen die verfügbaren Boards.
3. Die Boards auswählen, die in der persönlichen Planyt-Übersicht berücksichtigt werden sollen.
4. **Board-Auswahl speichern** anklicken.
5. Noch einmal synchronisieren.

Planyt zeigt anschließend offene Karten dieser Boards, die dem verbundenen Trello-Nutzer zugeordnet sind.

## Fehlerbehebung

### „Trello ist für diese Installation noch nicht freigeschaltet“

Die einmalige Admin-Konfiguration fehlt. Ausführen:

```bash
php bin/planyt trello:configure
```

### Callback funktioniert nicht

Prüfen, ob die Callback-URL in Trello und in Planyt exakt übereinstimmt. Unterschiede bei `http`/`https`, Hostname, Port oder Pfad führen zu Fehlern.

### Der Login funktioniert, aber keine Boards erscheinen

Prüfen:

- Wurde **Jetzt synchronisieren** ausgeführt?
- Hat der angemeldete Trello-Nutzer Zugriff auf die Boards?
- Sind die Karten dem Nutzer selbst zugewiesen?
- Sind `read:member:trello` und `read:board:trello` im OAuth-2.0-Client aktiviert?

## Sicherheitsregel

Planyt verwendet Trello ausschließlich lesend.

Es werden keine Karten erstellt, verschoben, kommentiert, archiviert oder abgeschlossen. Kalenderplanung in Planyt verändert niemals eine Trello-Karte.
