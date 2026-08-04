<?php

namespace App\Services;

use App\Models\RfidTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RfidTransactionReadModel
{
    private const INDEX_KEY = 'rfid:transactions:by-scanned-at';
    private const RECORD_KEY_PREFIX = 'rfid:transaction:';

    public function enabled(): bool
    {
        return config('rfid.read_model') === 'redis' && config('database.redis.client') !== null;
    }

    public function rebuild(): int
    {
        Redis::del(self::INDEX_KEY);
        $count = 0;

        RfidTransaction::query()->orderBy('id')->chunkById(500, function ($transactions) use (&$count) {
            foreach ($transactions as $transaction) {
                $this->upsert($transaction->toArray());
                $count++;
            }
        });

        return $count;
    }

    public function processOutbox(int $limit = 500): int
    {
        $changes = DB::table('rfid_transaction_cdc_outbox')->orderBy('id')->limit($limit)->get();

        foreach ($changes as $change) {
            if ($change->operation === 'DELETE') {
                $this->delete((int) $change->transaction_id);
            } else {
                $this->upsert(json_decode($change->payload, true, flags: JSON_THROW_ON_ERROR));
            }

            DB::table('rfid_transaction_cdc_outbox')->where('id', $change->id)->delete();
        }

        return $changes->count();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        if (! $this->enabled()) {
            return $this->databasePaginate($filters, $perPage);
        }

        try {
            $page = LengthAwarePaginator::resolveCurrentPage();
            $rows = $this->all()->filter(fn (array $row) => $this->matches($row, $filters))->values();

            return new LengthAwarePaginator(
                $rows->slice(($page - 1) * $perPage, $perPage)->map(fn (array $row) => $this->hydrate($row)),
                $rows->count(),
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
        } catch (\Throwable $exception) {
            report($exception);

            return $this->databasePaginate($filters, $perPage);
        }
    }

    public function count(): int
    {
        return (int) Redis::zcard(self::INDEX_KEY);
    }

    private function all(): Collection
    {
        $ids = Redis::zrevrange(self::INDEX_KEY, 0, -1);

        return collect($ids)->map(function ($id) {
            $payload = Redis::get(self::RECORD_KEY_PREFIX.$id);

            return $payload ? json_decode($payload, true) : null;
        })->filter();
    }

    private function upsert(array $transaction): void
    {
        $id = (int) $transaction['id'];
        $score = strtotime((string) $transaction['scanned_at']);

        Redis::set(self::RECORD_KEY_PREFIX.$id, json_encode($transaction, JSON_THROW_ON_ERROR));
        Redis::zadd(self::INDEX_KEY, $score, (string) $id);
    }

    private function delete(int $id): void
    {
        Redis::del(self::RECORD_KEY_PREFIX.$id);
        Redis::zrem(self::INDEX_KEY, (string) $id);
    }

    private function matches(array $row, array $filters): bool
    {
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        if ($search !== '' && ! str_contains(strtolower(implode(' ', [
            $row['campus_id'] ?? '',
            $row['cardholder_name'] ?? '',
            $row['rfid_code'] ?? '',
        ])), $search)) {
            return false;
        }

        if (($filters['status'] ?? '') !== '' && ($row['status'] ?? '') !== $filters['status']) {
            return false;
        }

        if (($filters['branch_id'] ?? null) && (int) ($row['branch_id'] ?? 0) !== (int) $filters['branch_id']) {
            return false;
        }

        $date = substr((string) ($row['scanned_at'] ?? ''), 0, 10);

        return (($filters['from'] ?? '') === '' || $date >= $filters['from'])
            && (($filters['to'] ?? '') === '' || $date <= $filters['to']);
    }

    private function hydrate(array $row): RfidTransaction
    {
        $transaction = new RfidTransaction;
        $transaction->setRawAttributes($row, true);
        $transaction->exists = true;

        return $transaction;
    }

    private function databasePaginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return RfidTransaction::query()
            ->with('branch')
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('campus_id', 'like', "%{$search}%")
                        ->orWhere('cardholder_name', 'like', "%{$search}%")
                        ->orWhere('rfid_code', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('scanned_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('scanned_at', '<=', $to))
            ->latest('scanned_at')
            ->paginate($perPage);
    }
}
