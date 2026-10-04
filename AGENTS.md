# Planyt Organisation contributor and AI rules

These rules apply to every human contributor and AI agent.

## Repository and workflow
- The repository is the source of truth.
- This repository is maintained directly on `main`. Do not create feature branches unless maintainers explicitly change this rule.
- Every repository change must be recorded in `Changelog.md`.

## Product boundaries
- Trello is the source of truth for tasks and is strictly read-only.
- Gmail is strictly read-only. Planyt must never send email, create drafts, label or archive mail.
- Google Drive is read-only by default.
- Google Calendar is the scheduling target and may be written only after explicit user intent.
- Calendar state must never complete, move or otherwise mutate a Trello card.
- The baseline has no server-side AI dependency. The Prompt Compiler only creates copyable text.

## Engineering
- Never commit secrets, OAuth tokens, API keys or credentials.
- OAuth scopes must be minimal and enforce read-only boundaries.
- PHP follows PSR-1, PSR-4 and PSR-12.
- Native JavaScript is the default.
- Deterministic reusable logic requires tests.
- Architecture changes require an ADR.
