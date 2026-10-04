# planyt.organisation

Planyt Organisation is a personal work overview that connects Trello, Gmail, Google Drive and Google Calendar without replacing the source systems.

## Product rules

- **Trello remains the source of truth for tasks.**
- Trello is strictly read-only.
- Gmail is strictly read-only; Planyt cannot send mail or create drafts.
- Google Drive is read-only.
- Google Calendar is the personal scheduling layer and is the only integration with write access.
- Calendar writes require an explicit user action.
- Calendar state never completes, moves or changes Trello cards.
- AI support is provided through the local Prompt Compiler; no AI API is required.

## Technical baseline

- PHP 8.4
- Composer
- JSON for portable user state
- encrypted file storage for OAuth tokens
- SQLite remains optional for later technical metadata/indexing
- native JavaScript
- PSR-1, PSR-4 and PSR-12
- direct maintenance on `main`

## Real integrations

### Google

One or more Google accounts can be connected per Planyt user.

Requested scopes:

- `gmail.readonly`
- `drive.readonly`
- `calendar.events.owned`

The Google OAuth client must be configured as a Web application and its callback URI must match `GOOGLE_REDIRECT_URI`.

### Trello

Trello uses OAuth 2.0 with a confidential client and PKCE.

Requested scopes:

- `read:member:trello`
- `read:board:trello`
- `offline_access`

No Trello write scope is requested anywhere in the application.

Each user can select which of their accessible Trello boards should contribute cards to their personal overview. Only open cards assigned to that Trello member are included.

## Local setup

```bash
composer install
cp .env.example .env
```

Set a long random `APP_KEY`, configure the Google and/or Trello OAuth clients, then start the prototype:

```bash
php -S 127.0.0.1:8080 -t public
```

The configured OAuth callback URLs must exactly match the URLs in `.env`.

## Runtime data

Runtime data never belongs in Git:

```
storage/
  users/
    <user-id>/
      dashboard.json
      preferences.json
      connections/
        google/
          <account>.token
        trello/
          <account>.token
```

OAuth connection files are encrypted with libsodium using a key derived from `APP_KEY`. The application stores no provider password.

## Synchronization

A sync:

1. pulls connected Trello boards and assigned open cards,
2. pulls recent Gmail inbox threads and identifies reply candidates,
3. reads the next 30 days of owned Google Calendar events,
4. matches Planyt-created calendar blocks back to their Trello source IDs,
5. writes the resulting personal view to the user's local `dashboard.json`.

Syncing never writes to Trello, Gmail or Drive.

## Scheduling

For an unscheduled Trello card, the user explicitly chooses:

- date,
- time,
- duration,
- destination Google account.

Only that submit action can create the calendar event. The event stores the Trello card ID as a private extended property, but nothing is written back to Trello.

## Prompt Compiler

Planyt can compile Gmail or Trello context into a copyable prompt. The prompt is only copied to the clipboard.

There is:

- no OpenAI API dependency,
- no AI usage billing in Planyt,
- no mail-send capability,
- no automatic external communication.

## Host update

Every installation follows `main`.

```bash
php bin/planyt update
```

The updater refuses local source modifications, requires the host to be on `main`, fetches `origin/main`, performs a fast-forward-only update and reinstalls production dependencies.

## Security

- Never commit `.env`, OAuth tokens or other secrets.
- Production installations should use HTTPS.
- Provider permissions are intentionally narrower than the provider capabilities.
- Any future request for Gmail/Trello write permissions requires a new ADR and an explicit maintainer decision.
