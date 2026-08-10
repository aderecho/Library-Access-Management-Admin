-- PostgreSQL one-time correction for existing RFID transaction scan times.
-- Assumption: every current rfid_transactions.scanned_at value is stored as
-- UTC in a timestamp-without-time-zone column and has not yet been converted.
--
-- The permanent backup table intentionally makes this script fail if it is
-- accidentally run a second time.

BEGIN;

LOCK TABLE rfid_transactions IN SHARE ROW EXCLUSIVE MODE;

CREATE TABLE rfid_transaction_scanned_at_backup_20260810 AS
SELECT id, scanned_at
FROM rfid_transactions
WHERE scanned_at IS NOT NULL;

ALTER TABLE rfid_transaction_scanned_at_backup_20260810
    ADD PRIMARY KEY (id);

-- Preview the supplied example. Expected Manila value: 2026-08-10 16:43:03.
SELECT
    TIMESTAMP '2026-08-10 08:43:03' AS utc_scan_time,
    (TIMESTAMP '2026-08-10 08:43:03' AT TIME ZONE 'UTC')
        AT TIME ZONE 'Asia/Manila' AS manila_scan_time;

UPDATE rfid_transactions AS rt
SET scanned_at = (backup.scanned_at AT TIME ZONE 'UTC')
    AT TIME ZONE 'Asia/Manila'
FROM rfid_transaction_scanned_at_backup_20260810 AS backup
WHERE rt.id = backup.id;

-- Review the converted range and row count before committing.
SELECT
    COUNT(*) AS converted_rows,
    MIN(scanned_at) AS earliest_manila_scan,
    MAX(scanned_at) AS latest_manila_scan
FROM rfid_transactions
WHERE id IN (
    SELECT id
    FROM rfid_transaction_scanned_at_backup_20260810
);

COMMIT;

-- Recovery query (run separately only if the conversion must be reversed):
-- BEGIN;
-- UPDATE rfid_transactions AS rt
-- SET scanned_at = backup.scanned_at
-- FROM rfid_transaction_scanned_at_backup_20260810 AS backup
-- WHERE rt.id = backup.id;
-- COMMIT;
