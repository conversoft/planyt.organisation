# planyt.organisation

Planyt Organisation is a personal work overview for Trello, Gmail, Google Drive, Google Calendar and optionally PROAD.

## Product rules

- Trello remains the source of truth for tasks and is strictly read-only.
- Gmail is strictly read-only. Planyt cannot send mail or create drafts.
- Google Drive is read-only.
- Google Calendar receives explicitly confirmed personal planning blocks.
- PROAD may receive explicitly confirmed business actions such as time bookings. Additional create actions are capability-gated by the connected user's PROAD rights.
- Calendar state never completes, moves or changes Trello cards.
- The Prompt Compiler creates copyable text only. Planyt has no outbound AI or communication action.

## User experience

Regular users do not configure OAuth clients, secrets, callback URLs or environment files.

Google is taken from the application's existing Google login. Planyt expects that login to provide an access token with these permissions:

- `gmail.readonly`
- `drive.readonly`
- `calendar.events.owned`

The dashboard only shows whether Google is available.

Trello is the only additional account connection visible to a user. The user clicks **Trello verbinden**, authorizes read access in Atlassian/Trello and returns to Planyt.

## Installation

Requirements:

- PHP 8.4
- Composer
- PHP extensions: cURL, JSON and Sodium

Install dependencies:

```bash
composer install
```

No `.env` file is required. On first start Planyt automatically creates its installation key in protected runtime storage.

For local development:

```bash
php -S 127.0.0.1:8080 -t public
```

## One-time Google setup

Google's OAuth application is configured once by the administrator. Regular users only see **Mit Google anmelden**.

Detailed step-by-step instructions:

- [Google OAuth einmalig einrichten](docs/admin/google-oauth-setup.md)

The installation stores the Google OAuth client configuration in protected runtime storage; no `.env` file is required.

## One-time Trello setup

Trello's OAuth application credentials are installation-level settings. They are configured once by the administrator, never by employees.

Detailed step-by-step instructions:

- [Trello OAuth einmalig einrichten](docs/admin/trello-oauth-setup.md)

The short setup command is:

```bash
php bin/planyt trello:configure
```

After that, every employee only uses the **Trello verbinden** button.

## Google user connection

After the one-time installation setup, each user only clicks **Mit Google anmelden**. Google handles account selection and consent, then Planyt stores the user's OAuth connection encrypted in runtime storage.

## PROAD setup

Detailed setup instructions:

- [PROAD einmalig verbinden](docs/admin/proad-setup.md)

Installation-level setup:

```bash
php bin/planyt proad:configure
```

Each user then enters only their personal PROAD API key under **Verbindungen → PROAD**.

## PROAD integration boundary

PROAD is planned as an optional integration with per-user rights.

Initial target for regular users:

- read available PROAD projects,
- book working time to a project/service code after explicit confirmation.

Additional capabilities for users whose PROAD account allows them:

- create projects,
- create contacts,
- create offers once the exact offer endpoint of the installed PROAD version has been verified.

Planyt never invents an "admin" role on its own. It derives capabilities from PROAD permissions and lets PROAD enforce the final authorization.

Architecture details: [ADR 0004](docs/adr/0004-proad-integration.md).

## Runtime data

Runtime data never belongs in Git:

```
storage/
  system/
    config.json
  users/
    <user-id>/
      dashboard.json
      preferences.json
      tokens.json
```

`storage/system/config.json` contains the automatically generated installation key and optional installation-level provider configuration.

`tokens.json` contains the user's Google, Trello and future PROAD connection credentials. Provider payloads are encrypted with libsodium before they are written.

## Synchronization

A sync:

1. reads assigned open Trello cards from the selected boards,
2. reads recent Gmail threads and identifies likely reply candidates,
3. reads owned Google Calendar events,
4. compares Trello cards with Planyt-created work blocks,
5. writes the derived personal overview to the user's local JSON state.

Sync never writes to Trello, Gmail or Drive.

## Scheduling

For an unscheduled Trello card, the user explicitly chooses date, time, duration and Google account. Only this confirmed action can create a Calendar event. Nothing is written back to Trello.

## Host update

Every installation follows `main`:

```bash
php bin/planyt update
```

The updater refuses local source modifications and only fast-forwards to `origin/main`.

## Security

- No secrets are committed.
- No `.env` setup is required.
- The installation key is generated automatically and stored outside the public web root.
- User OAuth data is encrypted at rest.
- Gmail, Drive and Trello write permissions are forbidden by architecture.
- Any future expansion of provider permissions requires an explicit ADR.
