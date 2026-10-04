# Changelog

## 2026-10-04

### Added
- Initial PHP prototype with personal dashboard.
- File-based demo state for Trello, Gmail and Google Calendar.
- Prompt Compiler without AI API dependency.
- Strict read-only boundaries for Trello and Gmail.
- Explicit-user-action boundary for Calendar writes.
- Main-only workflow and host updater concept.
- Quality workflow and tests.

- Added real Google OAuth integration for multiple accounts.
- Added real Trello OAuth 2.0 confidential-client integration with PKCE.
- Added encrypted file storage for OAuth credentials.
- Added read-only Gmail thread ingestion with complete last-message body.
- Added read-only Drive search integration.
- Added owned-calendar event reads and explicit-action calendar writes.
- Added per-user Trello board selection and cross-source synchronization.
- Added live dashboard connection and scheduling controls.
- Added integration scope, PKCE and encrypted-token safety tests.

- Fixed missing `UserPreferencesRepository` import in the live dashboard.

- Added explicit Google and Trello Connect buttons.
- Unified Google and Trello OAuth storage into per-user `tokens.json`, with encrypted provider payloads and legacy token migration.

- Removed the standalone Google OAuth flow and duplicate Google client configuration.
- Reused the existing Google login as the source of Google identity and access tokens.
- Removed the normal runtime dependency on `.env`.
- Added automatic installation-key generation in protected storage.
- Added one-time administrator Trello configuration via `php bin/planyt trello:configure`.
- Simplified employee-facing connection language to Google login status and a single Trello Connect action.

- Added an administrator-facing step-by-step Google OAuth setup guide and linked it from the README.

- Added a step-by-step Trello OAuth admin setup guide and linked it from the README.
- Bound Trello OAuth state to the initiating user and preserved rotated refresh tokens.

Author: OpenAI GPT-5.6 Sol
