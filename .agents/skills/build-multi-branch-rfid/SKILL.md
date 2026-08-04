---
name: build-multi-branch-rfid
description: Design, implement, review, or extend branch-isolated RFID facility monitoring in Laravel applications. Use when scanners belong to physical branches, RFID transactions must inherit scanner branch identity, entry monitors must only see their assigned branch, live broadcasts require tenant-safe private channels, or administrators need branch/scanner/monitor assignment workflows.
---

# Build Multi-Branch RFID

Treat the scanner credential as the trusted source of branch identity. Never accept `branch_id` from an RFID scan request body.

## Workflow

1. Inspect scanner authentication, the transaction model, monitor query, broadcast channel, user authorization, and tests.
2. Read [references/architecture.md](references/architecture.md) before changing data flow or authorization.
3. Preserve unrelated working-tree changes and follow repository conventions.
4. Add a branch entity with a stable unique code and active state.
5. Assign each scanner credential to exactly one active branch.
6. Copy the authenticated scanner's `branch_id` onto every transaction, including invalid cards.
7. Assign monitor users to one branch; allow an explicit cross-branch role only when required.
8. Scope the monitor query by authorized branch.
9. Broadcast on a branch-specific private channel and authorize the same boundary server-side.
10. Provide branch, scanner, and monitor assignment UI.
11. Test positive visibility and negative isolation across two branches.

## Security invariants

- Derive branch identity from the authenticated scanner token.
- Reject inactive scanners, inactive branches, and missing assignments.
- Persist `branch_id` before broadcasting.
- Authorize WebSocket subscriptions server-side.
- Prevent Branch A monitors from querying, rendering, or subscribing to Branch B scans.
- Keep unknown-card transactions branch-scoped.
- Preserve audit history with restrictive foreign-key deletion.

## Verification

Run focused isolation tests, then the full suite. Assert that a Branch A scanner creates a Branch A transaction, Branch A monitors cannot see Branch B activity, and cross-branch channel authorization is denied.

Report migration sequencing. Backfill existing scanners and transactions before enforcing non-null constraints.
