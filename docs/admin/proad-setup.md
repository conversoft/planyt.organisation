# PROAD einmalig verbinden

PROAD wird in Planyt zweistufig eingerichtet.

## 1. Installation freischalten

Der Administrator hinterlegt einmalig die Basis-Adresse der eigenen PROAD-Installation:

```bash
php bin/planyt proad:configure
```

Danach fragt Planyt nach:

```text
PROAD Basis-URL:
```

Beispiel:

```text
https://proad.example.com
```

Diese Adresse wird nur in `storage/system/config.json` gespeichert und nicht ins Git-Repository geschrieben.

## 2. Persönlichen PROAD API-Key erzeugen

Jeder Nutzer verwendet seinen eigenen PROAD API-Key.

Der API-Key muss im PROAD-Benutzerkontext erzeugt werden. Welche Aktionen später möglich sind, richtet sich nach den Rechten dieses PROAD-Benutzers.

## 3. In Planyt verbinden

1. Planyt öffnen.
2. Zu **Verbindungen → PROAD** gehen.
3. Persönlichen API-Key eintragen.
4. **PROAD verbinden** anklicken.

Der API-Key wird verschlüsselt in der persönlichen `tokens.json` gespeichert.

Danach zeigt Planyt:

```text
PROAD
✓ Verbunden
```

## Rechte

Normale Nutzer sollen damit Projekte lesen und eigene Zeiten buchen können.

Weitere Aktionen wie Projekte, Kontakte oder Angebote anlegen werden nur angeboten, wenn der verbundene PROAD-Benutzer dafür berechtigt ist.

Planyt vergibt selbst keine PROAD-Adminrechte.

## Sicherheit

- API-Keys niemals ins Git schreiben.
- PROAD-Schreibaktionen benötigen immer eine ausdrückliche Nutzeraktion.
- Trello und Gmail werden durch PROAD-Aktionen niemals verändert.
