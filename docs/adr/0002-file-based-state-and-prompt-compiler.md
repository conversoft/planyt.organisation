# ADR 0002: File-based state and Prompt Compiler

Status: Accepted  
Date: 2026-10-04

## Decision

Portable user-facing state is represented as JSON. SQLite may be introduced for technical metadata, indexes and synchronization state.

The baseline application contains a Prompt Compiler rather than a server-side AI API integration. It compiles selected context into text for the user's clipboard.

## Rationale

File-based state keeps installations portable and inspectable. A Prompt Compiler provides practical AI assistance without unpredictable per-call application costs or an open-ended AI proxy.

## Consequences

The core application must remain useful without an AI provider. A future AI transport may consume the same compiled prompt, but it must be optional and require a separate architectural decision.
