#!/usr/bin/env bash

set -euo pipefail

cd "$(dirname "$0")"

for command in brew php composer npm lsof; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command" >&2
        exit 1
    fi
done

release_port() {
    local port="$1"
    local label="$2"
    local pids

    pids="$(lsof -tiTCP:"$port" -sTCP:LISTEN 2>/dev/null || true)"

    if [[ -z "$pids" ]]; then
        return
    fi

    echo "Stopping stale $label listener on port $port..."
    kill $pids 2>/dev/null || true

    for _ in {1..20}; do
        if ! lsof -tiTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1; then
            return
        fi

        sleep 0.25
    done

    echo "Could not release port $port. Stop the process using it, then run ./start.sh again." >&2
    exit 1
}

release_port 8000 "Laravel"
release_port 8080 "Reverb"
release_port 5173 "Vite"

echo "Starting PostgreSQL and Redis..."
brew services start postgresql@14
brew services start redis

echo "Applying database migrations..."
php artisan migrate --force

echo "Starting Laravel, Redis queue worker, logs, Vite, and Reverb..."
composer run dev
