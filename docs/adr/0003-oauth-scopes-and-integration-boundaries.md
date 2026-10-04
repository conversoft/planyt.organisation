# ADR 0003: OAuth scopes and integration boundaries

Status: Accepted  
Date: 2026-10-04

## Decision

Planyt Organisation uses per-user OAuth connections and requests only the permissions needed by the product contract.

Google:
- Gmail: `gmail.readonly`
- Drive: `drive.readonly`
- Calendar: `calendar.events.owned`

Trello:
- `read:member:trello`
- `read:board:trello`
- `offline_access`

Trello uses OAuth 2.0 confidential-client authorization with PKCE. Google uses the OAuth 2.0 web-server authorization-code flow with offline access.

Refresh and access tokens are encrypted at rest with libsodium and a key derived from the host-provided `APP_KEY`.

## Responsibility boundary

No Gmail or Trello write scope may be introduced without a new accepted ADR.

Calendar writes are permitted only for user-owned calendars and only following an explicit user submit action. A synchronization process cannot create calendar events.

Planyt never sends external communication and never treats a calendar event as completion of a Trello task.

## Consequences

The application can aggregate and schedule work while preserving Trello as the task source of truth and the user as the responsible sender of external communication.
