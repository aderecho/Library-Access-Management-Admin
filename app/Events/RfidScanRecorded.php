<?php

namespace App\Events;

use App\Models\RfidTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RfidScanRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RfidTransaction $transaction) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.rfid-scans'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'rfid.scan.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'transactionId' => $this->transaction->id,
        ];
    }
}
