<?php

namespace App\Console\Commands;

use App\Services\RfidTransactionReadModel;
use Illuminate\Console\Command;

class SyncRfidTransactionsToRedis extends Command
{
    protected $signature = 'rfid:sync-redis {--rebuild} {--once}';

    protected $description = 'Synchronize PostgreSQL RFID transaction changes into the Redis read model';

    public function handle(RfidTransactionReadModel $readModel): int
    {
        if ($this->option('rebuild')) {
            $this->info("Rebuilt {$readModel->rebuild()} RFID transactions in Redis.");
        }

        do {
            $processed = $readModel->processOutbox();

            if ($this->option('once')) {
                $this->info("Processed {$processed} CDC changes.");

                return self::SUCCESS;
            }

            if ($processed === 0) {
                usleep(250_000);
            }
        } while (true);
    }
}
