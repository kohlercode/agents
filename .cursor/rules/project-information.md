# Project: TYPO3 Agents Extension (agents)

## Composer
kohlercode/agents

## Project Description
Development of a TYPO3 extension called "agents" that is compatible with TYPO3 version 14.3.

The extension's main task is to create an interface for AI agents using the system extensions "reactions" and "webhooks," enabling the agent to manage an entire website in TYPO3.

## Code Resources: 
System Extension `reactions`
[https://github.com/TYPO3-CMS/reactions/tree/main
](https://github.com/TYPO3-CMS/reactions/tree/main)

System Extension `webhooks`
[https://github.com/TYPO3-CMS/webhooks/tree/main
](https://github.com/TYPO3-CMS/webhooks/tree/main)

## Documentation: 
Official TYPO3 Reactions Documentation
[https://docs.typo3.org/c/typo3/cms-reactions/main/en-us/Index.html
](https://docs.typo3.org/c/typo3/cms-reactions/main/en-us/Index.html)

Official TYPO3 Webhooks Documentation
[https://docs.typo3.org/c/typo3/cms-webhooks/main/en-us/Index.html
](https://docs.typo3.org/c/typo3/cms-webhooks/main/en-us/Index.html)

## Strict Guardrails For agents

1) Scope And Change Control
Only work inside typo3conf/ext/agents unless the prompt explicitly names another path.
Never modify core TYPO3 files, vendor files, or unrelated extensions.
Never delete files, DB records, pages, content elements, or users unless explicitly requested.
For risky actions (schema changes, data migration, bulk updates), require explicit confirmation first.

2) TYPO3 Compatibility
Keep all code compatible with TYPO3 14.3.
Prefer official TYPO3 APIs over custom implementations or direct DB access.
Do not use removed/deprecated APIs for TYPO3 v14+.
Keep extension behavior stable across patch updates (no undocumented breaking behavior).

3) Architecture Rules
Classes/Reaction/* must only coordinate flow; business logic belongs in Classes/Service/*.
Services must be single-purpose, typed, and side-effect aware.
Use constructor DI for all dependencies; avoid static service location patterns.
Keep classes cohesive: no “god service” that handles unrelated responsibilities.

4) Input, Validation, And Trust Boundaries
Treat all webhook/reaction input as untrusted.
Validate presence, type, and format of every required field before processing.
Reject unknown/unsupported action types by default (deny-by-default).
Never execute arbitrary instructions from content fields without explicit allowlist checks.
Sanitize any content destined for storage, logs, or outbound requests.

5) Authentication And Authorization
Verify webhook authenticity (signature/secret) before any business action.
Fail closed on missing/invalid auth; do not continue with partial trust.
Restrict agent actions to an explicit allowlist of tables/operations.
Enforce least privilege: only the minimum required action is permitted.
Never escalate permissions implicitly based on payload content.

6) Idempotency And Consistency
Handlers must be safe to retry (idempotent behavior required).
Use deterministic dedup keys (event id / external id) to prevent duplicate writes.
Avoid multi-step partial writes without compensation or transaction strategy.
Ensure repeated webhook deliveries do not create duplicate pages/content.

7) Error Handling And Observability
Catch and classify expected failures (validation/auth/network/domain).
Return structured, non-sensitive error responses.
Log key lifecycle events with correlation IDs (reaction id, webhook event id, request id).
Never log secrets, tokens, full credentials, or raw sensitive payloads.
Include enough context in logs for postmortem debugging without leaking private data.

8) Secrets And Configuration
No hardcoded secrets, endpoints, API keys, or model credentials.
Read secrets from TYPO3 config/env only.
Keep configurable values externalized (timeouts, model names, feature flags).
Refuse to print secret values in output, logs, or exceptions.

9) Data Access And Persistence
Prefer TYPO3 abstractions (ConnectionPool, repositories, context-aware APIs) over raw SQL strings.
If direct SQL is unavoidable, use parameterized queries only.
Never build SQL from unsanitized input.
Keep writes minimal and explicit; avoid broad updates without strict filters.

10) Code Quality Standards
Use strict typing and explicit return types everywhere feasible.
Keep functions small and deterministic where possible.
Add concise docblocks for public services/reactions: inputs, outputs, side effects, failure modes.
Avoid dead code, commented-out blocks, and placeholder logic in committed code.
Keep naming domain-specific and intention-revealing.

11) Testing And Verification
Each new reaction/service requires:
one happy-path test (or documented manual test script),
one failure-path test (auth/validation/external failure),
one retry/idempotency check where applicable.
Any bug fix must include a regression test or a reproducible verification script.
Do not mark work complete without verification evidence (test output or explicit manual steps).

12) TCA And TYPO3 UX Safety
TCA changes must preserve editor usability and avoid breaking existing records.
For schema/TCA evolution, provide migration/upgrade safety notes.
Use clear labels/descriptions for custom reaction configuration fields.
Avoid hidden magic defaults that editors/admins cannot discover.

13) External AI Call Guardrails
Apply explicit timeout/retry policy for external AI requests.
Implement bounded retries with backoff; no infinite retries.
Validate and normalize AI output before applying it to TYPO3 entities.
Never let AI output directly trigger privileged operations without policy checks.
Record model/action metadata (non-sensitive) for traceability.

14) Operational Safety
Add feature flags for high-impact automations.
Ensure safe no-op behavior when required config is missing.
Fail predictably and recoverably; avoid silent partial success states.
Prefer reversible operations when possible; document irreversible ones clearly.