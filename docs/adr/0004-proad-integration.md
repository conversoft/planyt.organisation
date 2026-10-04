# ADR 0004: PROAD integration and write boundaries

## Status

Accepted

## Context

Planyt should support PROAD as an optional business-system integration.

Regular users should be able to:

- read the PROAD projects available to them,
- book their own working time to a PROAD project and service/activity.

Users with the corresponding PROAD rights should additionally be able to:

- create projects,
- create contacts,
- create offers, once the concrete offer endpoint of the installed PROAD version has been verified.

PROAD API v5 documents per-user API keys and enforces user rights, data rights, settings and licence restrictions on API requests.

## Decision

PROAD is a separate integration boundary and is not treated like Trello.

Trello and Gmail remain strictly read-only.

PROAD may receive writes, but only when all of the following are true:

1. the user explicitly triggered the action in Planyt,
2. the connected PROAD identity exposes the required capability,
3. PROAD itself accepts the operation under that user's rights,
4. the action is shown to the user before submission.

Planyt must never infer administrator privileges from its own UI role. Capabilities must be derived from PROAD rights or from verified API behaviour.

The initial capability model is:

- `read_projects`
- `book_time`
- `create_project`
- `create_contact`
- `create_offer`

The first implementation target is project lookup and time booking.

Project/contact/offer creation is exposed only for users with the matching capability.

Offer creation remains disabled until the concrete endpoint and payload for the installed PROAD version have been verified.

## Authentication and secrets

The legacy PROAD API v5 documentation describes per-user API keys sent in an `apikey` header.

Planyt must store each user's PROAD credential encrypted in the existing per-user token store. API keys must never be committed to Git or stored in browser-visible source.

The PROAD host/base URL is installation-level configuration and belongs in protected runtime configuration, not in `.env`.

## User experience

Normal user:

- connect PROAD once,
- select a project,
- select service/activity,
- enter date, duration and description,
- explicitly confirm the time booking.

Privileged user:

- receives additional visible actions only when the required PROAD capabilities are available,
- may create project/contact/offer records through explicit confirmation forms.

## Consequences

PROAD becomes the second integration besides Google Calendar that may receive writes.

All PROAD mutations require an explicit user action and a capability check.

Trello remains the source of truth for tasks and is never changed by PROAD operations.
