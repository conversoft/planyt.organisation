# planyt.organisation

Planyt Organisation is a personal work overview that connects Trello, Gmail and Google Calendar without replacing the systems people already use.

## Product rule

- **Trello remains the source of truth for tasks.**
- Gmail is an additional source for actionable communication.
- Google Calendar is the personal scheduling layer.
- Planyt Organisation aggregates, prioritizes and schedules work.
- Calendar state never automatically completes, moves or changes Trello cards.
- If relevant work cannot be scheduled reliably, the user must be asked to schedule it.

## Technical baseline

- PHP 8.4
- Composer
- SQLite for technical metadata where useful
- JSON files for portable user state and derived views
- Native JavaScript first
- PSR-1, PSR-4 and PSR-12
- Repository rules aligned with Planet Ark
- Main branch is the only development/release branch for this repository

## Planned integrations

- Trello: read/search relevant boards and cards; Trello remains authoritative
- Gmail: multiple Google accounts per user; detect mail that needs action/reply
- Google Calendar: read availability and create personal work blocks/events
- Google Drive: link relevant files and context
- WhatsApp: optional later integration, not part of the first baseline

## Repository structure

```
bin/                    CLI entrypoints
config/                 non-secret application configuration
docs/                   architecture and ADRs
public/                 web entrypoint and public assets
src/                    application code
storage/                runtime data, never committed
tests/                  automated tests
organisation.json       machine-readable application manifest
```

## Host update concept

Every installation follows `main`. The planned command is:

```bash
php bin/planyt update
```

The updater must only fast-forward to the current remote `main`, refuse to overwrite local source modifications, preserve local runtime/configuration data, install production dependencies and run deterministic maintenance steps.

## Security

OAuth credentials, refresh tokens and other secrets must never be committed. Runtime secrets belong in environment configuration or protected local storage outside the public web root.
