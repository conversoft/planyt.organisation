# Google OAuth einmalig einrichten

Diese Anleitung ist **nur für den Administrator der Installation** gedacht. Mitarbeitende müssen keine Client-ID, kein Secret und keine Callback-URL kennen.

Nach dieser einmaligen Einrichtung sehen Mitarbeitende in Planyt nur noch den Button **„Mit Google anmelden“**.

## Was wird eingerichtet?

Planyt benötigt von Google drei Berechtigungen:

- Gmail **lesen**: `gmail.readonly`
- Google Drive **lesen**: `drive.readonly`
- Termine in eigenen Google-Kalendern **lesen und schreiben**: `calendar.events.owned`

Planyt kann damit **keine E-Mails senden** und keine Drive-Dateien verändern.

## 1. Google-Cloud-Projekt auswählen oder anlegen

1. Öffne die Google Cloud Console.
2. Wähle ein vorhandenes Projekt für Planyt oder lege ein neues Projekt an, zum Beispiel **Planyt Organisation**.
3. Aktiviere in diesem Projekt:
   - Gmail API
   - Google Drive API
   - Google Calendar API

## 2. Google Auth Platform einrichten

Öffne im Projekt **Google Auth Platform**.

Unter **Branding** bzw. dem Zustimmungsbildschirm:

1. App-Name: zum Beispiel **Planyt Organisation**
2. Support-E-Mail auswählen.
3. Kontakt-E-Mail hinterlegen.

Unter **Audience**:

- Wenn Planyt ausschließlich in eurer eigenen Google-Workspace-Organisation verwendet wird und Google die Option anbietet, kann **Internal** verwendet werden.
- Andernfalls **External** wählen und während der Testphase die gewünschten Google-Konten als Testnutzer hinzufügen.

## 3. Berechtigungen hinzufügen

Unter **Data Access / Datenzugriff** diese Scopes hinzufügen:

```text
https://www.googleapis.com/auth/gmail.readonly
https://www.googleapis.com/auth/drive.readonly
https://www.googleapis.com/auth/calendar.events.owned
```

Keine Gmail-Send- oder Gmail-Modify-Scopes hinzufügen.

## 4. OAuth-Client erstellen

Unter **Google Auth Platform → Clients**:

1. **Client erstellen** anklicken.
2. Anwendungstyp **Webanwendung** wählen.
3. Name zum Beispiel **Planyt Organisation Web**.
4. Unter **Autorisierte Weiterleitungs-URIs** die Callback-Adresse von Planyt eintragen.

Für die lokale Installation aus unserem aktuellen Setup beispielsweise:

```text
https://planyt-organization.test/oauth/google/callback.php
```

Für eine andere Installation entsprechend:

```text
https://DEIN-HOST/oauth/google/callback.php
```

Die URL muss exakt mit der später in Planyt eingetragenen Callback-URL übereinstimmen.

Für diesen serverseitigen OAuth-Flow ist keine JavaScript-Origin-Konfiguration erforderlich.

## 5. Client-ID und Client-Secret kopieren

Nach dem Erstellen zeigt Google:

- **Client-ID**
- **Client-Secret**

Diese beiden Werte nicht ins Git-Repository schreiben und nicht an Mitarbeitende verteilen.

## 6. Google einmalig in Planyt freischalten

Im Projektverzeichnis auf dem Server bzw. Entwicklungsrechner ausführen:

```bash
php bin/planyt google:configure
```

Planyt fragt nacheinander:

```text
Client-ID:
Client-Secret:
Callback-URL:
```

Als Callback-URL exakt dieselbe Adresse verwenden, die in Google eingetragen wurde, zum Beispiel:

```text
https://planyt-organization.test/oauth/google/callback.php
```

Planyt speichert diese Installationskonfiguration geschützt unter `storage/system/config.json`. Sie wird nicht ins Repository eingecheckt.

## 7. Als Mitarbeiter verbinden

Danach ist für Mitarbeitende nur noch Folgendes nötig:

1. Planyt öffnen.
2. Unter **Verbindungen → Google** auf **„Mit Google anmelden“** klicken.
3. Google-Konto auswählen.
4. Berechtigungen bestätigen.
5. Nach der Rückkehr zeigt Planyt das verbundene Google-Konto an.

Wenn der Nutzer im Browser bereits bei Google angemeldet ist, muss normalerweise kein Passwort erneut eingegeben werden. Google zeigt lediglich Kontoauswahl und Zustimmung.

## 8. Funktion prüfen

Nach erfolgreicher Verbindung:

1. **Jetzt synchronisieren** anklicken.
2. Kalendertermine sollten unter **Heute** erscheinen.
3. Gmail-Nachrichten mit möglichem Handlungsbedarf sollten im E-Mail-Bereich erscheinen.
4. Bei einer Trello-Aufgabe kann ein persönlicher Kalenderblock angelegt werden.

## Fehlerbehebung

### „Google ist für diese Installation noch nicht freigeschaltet“

Die einmalige Admin-Konfiguration fehlt. Noch einmal ausführen:

```bash
php bin/planyt google:configure
```

### `redirect_uri_mismatch`

Die Callback-URL in Google und die bei `google:configure` eingetragene URL stimmen nicht exakt überein. Auf Protokoll (`http`/`https`), Hostname, Port, Pfad und abschließenden Slash achten.

### Google zeigt „App nicht bestätigt“ oder der Nutzer darf sich nicht anmelden

Prüfen:

- Audience korrekt eingestellt?
- Bei External/Testbetrieb ist das Google-Konto als Testnutzer eingetragen?
- Gmail API, Drive API und Calendar API im Projekt aktiviert?
- Die drei benötigten Scopes im Google-Zustimmungsbildschirm hinterlegt?

## Sicherheitsregel

Die Google-OAuth-App wird einmal pro Planyt-Installation administrativ eingerichtet. Mitarbeitende sehen und bearbeiten weder Client-ID noch Client-Secret.

Die persönlichen Access- und Refresh-Tokens werden pro Nutzer verschlüsselt in `storage/users/<user>/tokens.json` gespeichert.
