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

Author: OpenAI GPT-5.6 Sol
