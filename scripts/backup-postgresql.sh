#!/usr/bin/env bash

set -Eeuo pipefail

script_dir="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
project_root="$(CDPATH= cd -- "$script_dir/.." && pwd)"
backup_dir="${1:-$project_root/storage/app/backups/database}"
retention_days="${BACKUP_RETENTION_DAYS:-0}"
lock_dir="$backup_dir/.backup.lock"
temporary_dump=""
lock_acquired=0

fail() {
    echo "Backup failed: $*" >&2
    exit 1
}

cleanup() {
    if [[ -n "$temporary_dump" && -f "$temporary_dump" ]]; then
        rm -f -- "$temporary_dump"
    fi

    if (( lock_acquired == 1 )) && [[ -d "$lock_dir" ]]; then
        rmdir -- "$lock_dir" 2>/dev/null || true
    fi
}

laravel_db_config() {
    local config_key="$1"

    APP_BACKUP_CONFIG_KEY="$config_key" php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $value = $app->make("db")->connection()->getConfig(getenv("APP_BACKUP_CONFIG_KEY"));
        if (is_bool($value)) {
            echo $value ? "1" : "0";
        } elseif (is_scalar($value)) {
            echo $value;
        }
    '
}

trap cleanup EXIT INT TERM

cd "$project_root"

command -v php >/dev/null 2>&1 || fail "php is not installed or not in PATH."
command -v pg_dump >/dev/null 2>&1 || fail "pg_dump is not installed or not in PATH."
command -v pg_restore >/dev/null 2>&1 || fail "pg_restore is not installed or not in PATH."
[[ -f .env ]] || fail "$project_root/.env does not exist."
[[ "$retention_days" =~ ^[0-9]+$ ]] || fail "BACKUP_RETENTION_DAYS must be zero or a positive integer."

db_driver="$(laravel_db_config driver)"
[[ "$db_driver" == "pgsql" ]] || fail "Laravel's active database connection is '$db_driver', not 'pgsql'."

db_host="$(laravel_db_config host)"
db_port="$(laravel_db_config port)"
db_name="$(laravel_db_config database)"
db_user="$(laravel_db_config username)"
db_password="$(laravel_db_config password)"
db_sslmode="$(laravel_db_config sslmode)"

[[ -n "$db_host" ]] || fail "The PostgreSQL host is empty."
[[ -n "$db_port" ]] || fail "The PostgreSQL port is empty."
[[ -n "$db_name" ]] || fail "The PostgreSQL database name is empty."
[[ -n "$db_user" ]] || fail "The PostgreSQL username is empty."

mkdir -p -- "$backup_dir"
chmod 700 -- "$backup_dir"
mkdir -- "$lock_dir" 2>/dev/null || fail "another database backup is already running."
lock_acquired=1

safe_db_name="$(printf '%s' "$db_name" | tr -cs 'A-Za-z0-9._-' '_')"
timestamp="$(TZ=Asia/Manila date '+%Y%m%d_%H%M%S_PHT')"
final_dump="$backup_dir/${safe_db_name}_${timestamp}.dump"
temporary_dump="$(mktemp "$backup_dir/.${safe_db_name}.XXXXXX.tmp")"
chmod 600 -- "$temporary_dump"

export PGPASSWORD="$db_password"
if [[ -n "$db_sslmode" ]]; then
    export PGSSLMODE="$db_sslmode"
fi

echo "Creating PostgreSQL backup for '$db_name'..."
pg_dump \
    --host="$db_host" \
    --port="$db_port" \
    --username="$db_user" \
    --dbname="$db_name" \
    --format=custom \
    --compress=9 \
    --no-owner \
    --no-acl \
    --file="$temporary_dump"

unset PGPASSWORD db_password

[[ -s "$temporary_dump" ]] || fail "pg_dump produced an empty file."
pg_restore --list "$temporary_dump" >/dev/null || fail "pg_restore could not validate the dump."

mv -- "$temporary_dump" "$final_dump"
temporary_dump=""
chmod 600 -- "$final_dump"

if (( retention_days > 0 )); then
    find "$backup_dir" \
        -maxdepth 1 \
        -type f \
        -name "${safe_db_name}_*.dump" \
        -mtime "+$retention_days" \
        -delete
fi

backup_size="$(du -h "$final_dump" | awk '{print $1}')"
echo "Backup completed and verified: $final_dump ($backup_size)"
