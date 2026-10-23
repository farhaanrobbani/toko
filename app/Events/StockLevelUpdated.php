<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class StockLevelUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public int $version;

    public function __construct(
        public int $productId,
        public int $warehouseId,
        public float $available,
    ) {
        $this->available = max(0, round($available, 3));
        $this->version = (int) floor(microtime(true) * 1_000_000);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('stock.'.$this->warehouseId);
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }

    /**
     * @return array{product_id: int, warehouse_id: int, available: float, version: int}
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'available' => $this->available,
            'version' => $this->version,
        ];
    }
}
