# Branch-Isolation Architecture

## Trusted flow

`scanner token -> branch -> RFID transaction -> monitor query -> private branch channel`

The API request supplies only the RFID/cardholder identifier. Scanner middleware authenticates the token, loads its active branch, and attaches the scanner model to the request. The controller persists that branch on the transaction.

## Recommended relationships

- `branches hasMany scanner_tokens`
- `branches hasMany users`
- `branches hasMany rfid_transactions`
- `scanner_tokens belongsTo branch` (required)
- `users belongsTo branch` (required for branch monitors; optional for global administrators)
- `rfid_transactions belongsTo branch` (required after backfill)

Index transactions by `(branch_id, scanned_at)` for the latest-entry query.

## Live channel pattern

Use `branches.{branchId}.rfid-scans`. Authorize it only when the user has monitor permission and belongs to that branch or holds an explicit global role. Render the authorized branch ID into the monitor page for the browser subscription.

## Rollout sequence

1. Create branches.
2. Add nullable foreign keys.
3. Create a default/backfill branch for existing records.
4. Assign every scanner and monitor to the correct branch.
5. Verify isolation with two-branch tests.
6. Make scanner and transaction branch keys non-null when production data is clean.

Do not infer a physical branch from cardholder data, IP address, or a client request field.
