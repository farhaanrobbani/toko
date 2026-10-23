<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SaleStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array{summary: array<string, float|int>, payments: Collection<int, array<string, float|int|string>>, products: Collection<int, array<string, float|int|string>>, recent: Collection<int, array<string, mixed>>}
     */
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $posQuery = $this->posQuery($from, $to);
        $onlineQuery = $this->onlineQuery($from, $to);

        $pos = (clone $posQuery)
            ->selectRaw('COUNT(*) AS transactions, COALESCE(SUM(subtotal), 0) AS subtotal, COALESCE(SUM(discount), 0) AS discount, COALESCE(SUM(total), 0) AS total')
            ->first();

        $online = (clone $onlineQuery)
            ->selectRaw('COUNT(*) AS transactions, COALESCE(SUM(subtotal), 0) AS subtotal, COALESCE(SUM(shipping_cost), 0) AS shipping, COALESCE(SUM(total), 0) AS total')
            ->first();

        $posCogs = (float) SaleItem::query()
            ->whereIn('sale_id', (clone $posQuery)->select('id'))
            ->selectRaw('COALESCE(SUM(cost_price * quantity), 0) AS cogs')
            ->value('cogs');

        $onlineCogs = (float) OrderItem::query()
            ->whereIn('order_id', (clone $onlineQuery)->select('id'))
            ->selectRaw('COALESCE(SUM(cost_price * quantity), 0) AS cogs')
            ->value('cogs');

        $posTransactions = (int) $pos->transactions;
        $onlineTransactions = (int) $online->transactions;
        $transactions = $posTransactions + $onlineTransactions;

        $posTotal = (float) $pos->total;
        $onlineSubtotal = (float) $online->subtotal;
        $onlineShipping = (float) $online->shipping;
        $revenue = round($posTotal + $onlineSubtotal, 2);
        $cogs = round($posCogs + $onlineCogs, 2);

        return [
            'summary' => [
                'transactions' => $transactions,
                'pos_transactions' => $posTransactions,
                'online_transactions' => $onlineTransactions,
                'revenue' => $revenue,
                'shipping' => round($onlineShipping, 2),
                'total_collected' => round($revenue + $onlineShipping, 2),
                'discount' => round((float) $pos->discount, 2),
                'cogs' => $cogs,
                'gross_profit' => round($revenue - $cogs, 2),
                'average' => $transactions > 0 ? round($revenue / $transactions, 2) : 0,
            ],
            'payments' => $this->payments($posQuery, $onlineQuery),
            'products' => $this->products($posQuery, $onlineQuery),
            'recent' => $this->recent($posQuery, $onlineQuery),
        ];
    }

    /**
     * @return array<int, array<int, string|float>>
     */
    public function exportRows(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = [];

        $sales = $this->posQuery($from, $to)->with('items')->orderBy('created_at')->get();

        foreach ($sales as $sale) {
            $cogs = (float) $sale->items->sum(fn (SaleItem $item) => (float) $item->cost_price * (float) $item->quantity);

            $rows[] = [
                'at' => $sale->created_at,
                'row' => [
                    $sale->created_at->format('Y-m-d H:i'),
                    'POS',
                    $sale->sale_number,
                    $sale->customer_name ?: 'Umum',
                    $sale->payment_method->label(),
                    $sale->status->label(),
                    (string) $sale->subtotal,
                    (string) $sale->discount,
                    '0',
                    (string) $sale->total,
                    (string) round($cogs, 2),
                    (string) round((float) $sale->total - $cogs, 2),
                ],
            ];
        }

        $orders = $this->onlineQuery($from, $to)->with('items')->orderBy('confirmed_at')->get();

        foreach ($orders as $order) {
            $cogs = (float) $order->items->sum(fn (OrderItem $item) => (float) $item->cost_price * (float) $item->quantity);

            $rows[] = [
                'at' => $order->confirmed_at,
                'row' => [
                    $order->confirmed_at->format('Y-m-d H:i'),
                    'Online',
                    $order->order_number,
                    $order->customer_name,
                    $order->payment_method->label(),
                    $order->status->label(),
                    (string) $order->subtotal,
                    '0',
                    (string) $order->shipping_cost,
                    (string) $order->total,
                    (string) round($cogs, 2),
                    (string) round((float) $order->subtotal - $cogs, 2),
                ],
            ];
        }

        usort($rows, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        return array_map(fn (array $row) => $row['row'], $rows);
    }

    private function posQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$from, $to]);
    }

    private function onlineQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::Processing->value,
                OrderStatus::Shipped->value,
                OrderStatus::Completed->value,
            ])
            ->whereBetween('confirmed_at', [$from, $to]);
    }

    /**
     * @return Collection<int, array<string, float|int|string>>
     */
    private function payments($posQuery, $onlineQuery): Collection
    {
        $payments = [];

        $add = function (string $label, int $count, float $total) use (&$payments): void {
            if (! isset($payments[$label])) {
                $payments[$label] = ['label' => $label, 'count' => 0, 'total' => 0.0];
            }

            $payments[$label]['count'] += $count;
            $payments[$label]['total'] = round($payments[$label]['total'] + $total, 2);
        };

        $posPayments = (clone $posQuery)
            ->selectRaw('payment_method, COUNT(*) AS total_count, COALESCE(SUM(total), 0) AS total_amount')
            ->groupBy('payment_method')
            ->get();

        foreach ($posPayments as $row) {
            $add($row->payment_method->label(), (int) $row->total_count, (float) $row->total_amount);
        }

        $onlinePayments = (clone $onlineQuery)
            ->selectRaw('payment_method, COUNT(*) AS total_count, COALESCE(SUM(total), 0) AS total_amount')
            ->groupBy('payment_method')
            ->get();

        foreach ($onlinePayments as $row) {
            $add($row->payment_method->label(), (int) $row->total_count, (float) $row->total_amount);
        }

        return collect($payments)->sortByDesc('total')->values();
    }

    /**
     * @return Collection<int, array<string, float|int|string>>
     */
    private function products($posQuery, $onlineQuery): Collection
    {
        $products = [];

        $add = function (string $sku, string $name, float $quantity, float $revenue, float $cogs) use (&$products): void {
            $key = $sku !== '' ? $sku : $name;

            if (! isset($products[$key])) {
                $products[$key] = [
                    'sku' => $sku,
                    'name' => $name,
                    'quantity' => 0.0,
                    'revenue' => 0.0,
                    'cogs' => 0.0,
                ];
            }

            $products[$key]['quantity'] = round($products[$key]['quantity'] + $quantity, 3);
            $products[$key]['revenue'] = round($products[$key]['revenue'] + $revenue, 2);
            $products[$key]['cogs'] = round($products[$key]['cogs'] + $cogs, 2);
        };

        $posItems = SaleItem::query()
            ->whereIn('sale_id', (clone $posQuery)->select('id'))
            ->selectRaw('sku, MAX(product_name) AS product_name, COALESCE(SUM(quantity), 0) AS quantity, COALESCE(SUM(subtotal), 0) AS revenue, COALESCE(SUM(cost_price * quantity), 0) AS cogs')
            ->groupBy('sku')
            ->get();

        foreach ($posItems as $item) {
            $add((string) $item->sku, (string) $item->product_name, (float) $item->quantity, (float) $item->revenue, (float) $item->cogs);
        }

        $onlineItems = OrderItem::query()
            ->whereIn('order_id', (clone $onlineQuery)->select('id'))
            ->selectRaw('sku, MAX(product_name) AS product_name, COALESCE(SUM(quantity), 0) AS quantity, COALESCE(SUM(subtotal), 0) AS revenue, COALESCE(SUM(cost_price * quantity), 0) AS cogs')
            ->groupBy('sku')
            ->get();

        foreach ($onlineItems as $item) {
            $add((string) $item->sku, (string) $item->product_name, (float) $item->quantity, (float) $item->revenue, (float) $item->cogs);
        }

        return collect($products)
            ->map(function (array $product): array {
                $product['profit'] = round($product['revenue'] - $product['cogs'], 2);

                return $product;
            })
            ->sortByDesc('revenue')
            ->take(15)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recent($posQuery, $onlineQuery): Collection
    {
        $sales = (clone $posQuery)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Sale $sale) => [
                'type' => 'pos',
                'id' => $sale->id,
                'number' => $sale->sale_number,
                'at' => $sale->created_at,
                'customer' => $sale->customer_name ?: 'Umum',
                'payment' => $sale->payment_method->label(),
                'total' => (float) $sale->total,
                'status' => $sale->status->label(),
                'status_class' => $sale->status->badgeClass(),
            ]);

        $orders = (clone $onlineQuery)
            ->latest('confirmed_at')
            ->limit(10)
            ->get()
            ->map(fn (Order $order) => [
                'type' => 'online',
                'id' => $order->id,
                'number' => $order->order_number,
                'at' => $order->confirmed_at,
                'customer' => $order->customer_name,
                'payment' => $order->payment_method->label(),
                'total' => (float) $order->total,
                'status' => $order->status->label(),
                'status_class' => $order->status->badgeClass(),
            ]);

        return $sales
            ->concat($orders)
            ->sortByDesc(fn (array $row) => $row['at']->timestamp)
            ->take(10)
            ->values();
    }
}
