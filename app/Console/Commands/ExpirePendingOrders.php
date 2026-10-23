<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Batalkan pesanan online yang melewati batas pembayaran')]
class ExpirePendingOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OrderService $orderService): int
    {
        $count = $orderService->expirePending();

        $this->info("{$count} pesanan kedaluwarsa dibatalkan.");

        return self::SUCCESS;
    }
}
