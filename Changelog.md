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

- Fixed PHP 8.5 deprecation warnings by removing the no-op `curl_close()` call from the HTTP client.

- Added per-user local task states: `done` and `irrelevant`. These states only affect Planyt and never write to or mutate Trello.

- Trello items marked `done` or `irrelevant` are removed from the local dashboard immediately and remain filtered on future syncs; Trello is never changed.
- Added a per-user `Ausblenden` action for Gmail attention items. Hidden mail threads stay out of Planyt on future syncs while Gmail remains read-only.

- Fixed local Trello and Gmail status buttons by replacing brittle one-time PHP-session action tokens with stateless HMAC-signed local-action tokens.
- The dashboard now also filters locally completed/irrelevant Trello cards on page load so they disappear immediately and remain hidden without touching Trello.

- Reduced the desktop content width, gave the Today/Planning area a calmer fixed-proportion layout, and placed local task actions side by side for a more compact scan-friendly dashboard.

- Tuned the desktop UI for comfortable use at 100% browser zoom: narrower content column, slightly larger typography, more compact connection proportions, and tighter planning cards/forms.

- Added direct links from Trello task titles in Planyt to the original Trello cards; links open in a new tab and do not change Trello.

- Added the PROAD integration boundary with capability-gated writes for time booking and privileged project/contact/offer creation.
- Added ADR 0004 defining per-user PROAD rights, explicit-action requirements, encrypted per-user credentials, and the rule that offer creation stays disabled until the installed PROAD offer endpoint is verified.
- Added tests for the PROAD write policy.

- Added PROAD per-user connection support with encrypted personal API-key storage.
- Added one-time installation configuration for the PROAD base URL via `php bin/planyt proad:configure`.
- Added a simple PROAD connection card and setup guide; users only enter their personal API key.

- Converted the connections block to a collapsible `<details>`/`<summary>` section so it can be folded away when not needed.

- Made the connections disclosure arrow larger, high-contrast and easier to recognize as an interactive toggle.

- Added automatic CSS cache busting so frontend style changes, including the prominent connections toggle, appear immediately after updates.

- Email previews now preserve line breaks and aggressively wrap long URLs/signature fragments so mail content cannot overflow its card.

Author: OpenAI GPT-5.6 Sol
