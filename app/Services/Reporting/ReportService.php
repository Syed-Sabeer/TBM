<?php

namespace App\Services\Reporting;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Pull out the customer's data — item wise, colour wise, month wise."
 *
 * Every method takes an optional company. Pass one and the answer is that
 * account's own history for their portal; leave it null and it is the whole
 * book for the back office. One set of queries, two audiences, no chance of
 * the two drifting apart.
 *
 * Cancelled orders are excluded everywhere, because a cancelled order is not
 * revenue and showing it as such makes every figure a lie.
 */
class ReportService
{
    /* ------------------------------------------------------------ By month */

    /**
     * @return Collection<int, array{month:string, label:string, orders:int, pieces:int, value:float}>
     */
    public function byMonth(?Company $company = null, int $months = 12): Collection
    {
        $from = now()->startOfMonth()->subMonths($months - 1);

        $rows = $this->orders($company)
            ->where('placed_at', '>=', $from)
            ->selectRaw($this->monthExpression().' as month')
            ->selectRaw('count(*) as orders')
            ->selectRaw('sum(total_pieces) as pieces')
            ->selectRaw('sum(merchandise_total) as value')
            ->groupBy('month')
            ->pluck(null, 'month');

        // Fill the gaps, so a quiet month is a zero rather than a hole.
        return collect(range(0, $months - 1))
            ->map(function (int $back) use ($from, $rows) {
                $month = $from->copy()->addMonths($back);
                $key = $month->format('Y-m');
                $row = $rows->get($key);

                return [
                    'month' => $key,
                    'label' => $month->format('M Y'),
                    'short' => $month->format('M'),
                    'orders' => (int) ($row->orders ?? 0),
                    'pieces' => (int) ($row->pieces ?? 0),
                    'value' => (float) ($row->value ?? 0),
                ];
            });
    }

    /* ------------------------------------------------------------- By item */

    /**
     * Keyed on the customer item number, never the mill reference — this feeds
     * the customer portal as well as the back office.
     */
    public function byItem(?Company $company = null, ?Carbon $from = null, int $limit = 20): Collection
    {
        return $this->items($company, $from)
            ->selectRaw('order_items.sku, order_items.name')
            ->selectRaw('sum(order_items.quantity) as pieces')
            ->selectRaw('sum(order_items.line_total) as value')
            ->selectRaw('count(distinct order_items.order_id) as orders')
            ->groupBy('order_items.sku', 'order_items.name')
            ->orderByDesc('pieces')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'sku' => $row->sku,
                'name' => $row->name,
                'pieces' => (int) $row->pieces,
                'value' => (float) $row->value,
                'orders' => (int) $row->orders,
            ]);
    }

    /* ----------------------------------------------------------- By colour */

    public function byColour(?Company $company = null, ?Carbon $from = null, int $limit = 12): Collection
    {
        return $this->items($company, $from)
            ->selectRaw('order_items.colour_name')
            ->selectRaw('sum(order_items.quantity) as pieces')
            ->selectRaw('sum(order_items.line_total) as value')
            ->groupBy('order_items.colour_name')
            ->orderByDesc('pieces')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'colour' => $row->colour_name,
                'pieces' => (int) $row->pieces,
                'value' => (float) $row->value,
            ]);
    }

    /* --------------------------------------------------------- By category */

    public function byCategory(?Company $company = null, ?Carbon $from = null): Collection
    {
        return $this->items($company, $from)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name')
            ->selectRaw('sum(order_items.quantity) as pieces')
            ->selectRaw('sum(order_items.line_total) as value')
            ->groupBy('categories.name')
            ->orderByDesc('pieces')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->name,
                'pieces' => (int) $row->pieces,
                'value' => (float) $row->value,
            ]);
    }

    /* ------------------------------------------------------- By decoration */

    public function byDecoration(?Company $company = null, ?Carbon $from = null): Collection
    {
        return $this->items($company, $from)
            ->selectRaw('order_items.decoration')
            ->selectRaw('sum(order_items.quantity) as pieces')
            ->groupBy('order_items.decoration')
            ->orderByDesc('pieces')
            ->get()
            ->map(fn ($row) => [
                'decoration' => $row->decoration,
                'pieces' => (int) $row->pieces,
            ]);
    }

    /* ------------------------------------------------------------- By user */

    /** Who on the account is ordering — the shared-history view, broken down. */
    public function byUser(Company $company, ?Carbon $from = null): Collection
    {
        return $this->orders($company)
            ->when($from, fn ($q) => $q->where('placed_at', '>=', $from))
            ->join('users', 'users.id', '=', 'orders.placed_by_id')
            ->selectRaw('users.name, users.id')
            ->selectRaw('count(*) as orders')
            ->selectRaw('sum(orders.merchandise_total) as value')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->id,
                'name' => $row->name,
                'orders' => (int) $row->orders,
                'value' => (float) $row->value,
            ]);
    }

    /* -------------------------------------------------------- By warehouse */

    public function byWarehouse(?Company $company = null, ?Carbon $from = null): Collection
    {
        return $this->orders($company)
            ->when($from, fn ($q) => $q->where('placed_at', '>=', $from))
            ->join('warehouses', 'warehouses.id', '=', 'orders.warehouse_id')
            ->selectRaw('warehouses.code, warehouses.name')
            ->selectRaw('count(*) as orders')
            ->selectRaw('sum(orders.total_pieces) as pieces')
            ->groupBy('warehouses.code', 'warehouses.name')
            ->orderByDesc('pieces')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
                'orders' => (int) $row->orders,
                'pieces' => (int) $row->pieces,
            ]);
    }

    /* ---------------------------------------------------------- By account */

    /** Back office only: the customer league table. */
    public function byCompany(?Carbon $from = null, int $limit = 15): Collection
    {
        return $this->orders(null)
            ->when($from, fn ($q) => $q->where('placed_at', '>=', $from))
            ->join('companies', 'companies.id', '=', 'orders.company_id')
            ->selectRaw('companies.id, companies.name, companies.account_number')
            ->selectRaw('count(*) as orders')
            ->selectRaw('sum(orders.merchandise_total) as value')
            ->selectRaw('sum(orders.total_pieces) as pieces')
            ->groupBy('companies.id', 'companies.name', 'companies.account_number')
            ->orderByDesc('value')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'company_id' => $row->id,
                'name' => $row->name,
                'account_number' => $row->account_number,
                'orders' => (int) $row->orders,
                'pieces' => (int) $row->pieces,
                'value' => (float) $row->value,
            ]);
    }

    /* ------------------------------------------------------------ Headline */

    /**
     * The figures across the top of a dashboard, each with the same period a
     * year earlier so the change is honest rather than a comparison against
     * whatever happens to be in the table.
     */
    public function headline(?Company $company = null, int $months = 12): array
    {
        $now = now();
        $currentFrom = $now->copy()->subMonths($months);
        $priorFrom = $now->copy()->subMonths($months * 2);

        $current = $this->aggregate($company, $currentFrom, $now);
        $prior = $this->aggregate($company, $priorFrom, $currentFrom);

        return [
            'orders' => $current['orders'],
            'pieces' => $current['pieces'],
            'value' => $current['value'],
            'average_order' => $current['orders'] > 0
                ? round($current['value'] / $current['orders'], 2)
                : 0,
            'open_orders' => $this->orders($company)->open()->count(),
            'value_change' => $this->percentChange($prior['value'], $current['value']),
            'pieces_change' => $this->percentChange($prior['pieces'], $current['pieces']),
            'orders_change' => $this->percentChange($prior['orders'], $current['orders']),
        ];
    }

    /* ----------------------------------------------------------- Internals */

    private function aggregate(?Company $company, Carbon $from, Carbon $to): array
    {
        $row = $this->orders($company)
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('count(*) as orders, sum(total_pieces) as pieces, sum(merchandise_total) as value')
            ->first();

        return [
            'orders' => (int) ($row->orders ?? 0),
            'pieces' => (int) ($row->pieces ?? 0),
            'value' => (float) ($row->value ?? 0),
        ];
    }

    private function percentChange(float $before, float $after): ?float
    {
        // No base period means no honest comparison — the view shows a dash.
        if ($before <= 0) {
            return null;
        }

        return round(($after - $before) / $before * 100, 1);
    }

    private function orders(?Company $company)
    {
        return Order::query()
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->when($company, fn ($q) => $q->where('orders.company_id', $company->id));
    }

    private function items(?Company $company, ?Carbon $from)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->when($company, fn ($q) => $q->where('orders.company_id', $company->id))
            ->when($from, fn ($q) => $q->where('orders.placed_at', '>=', $from));
    }

    /**
     * Month grouping differs between drivers; keeping the difference in one
     * expression means the rest of the class stays portable.
     */
    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', placed_at)",
            'pgsql' => "to_char(placed_at, 'YYYY-MM')",
            'sqlsrv' => "format(placed_at, 'yyyy-MM')",
            default => "date_format(placed_at, '%Y-%m')",
        };
    }
}
