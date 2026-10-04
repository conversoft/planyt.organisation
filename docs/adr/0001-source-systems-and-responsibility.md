# ADR 0001: Source systems and human responsibility

Status: Accepted  
Date: 2026-10-04

## Decision

Trello remains authoritative for tasks. Gmail and Trello integrations are read-only. Google Drive is read-only by default. Google Calendar is the only baseline integration allowed to receive writes, and only for personal scheduling after explicit user intent.

Planyt Organisation never sends external communication. It does not create Gmail drafts and does not provide a hidden sending path.

Calendar state must never complete, move or otherwise mutate a Trello card.

## Rationale

The application exists to improve visibility and personal planning without introducing a competing task system or obscuring responsibility for external communication.

## Consequences

OAuth scopes must enforce these boundaries. Features that require broader scopes need a new ADR and an explicit maintainer decision.
