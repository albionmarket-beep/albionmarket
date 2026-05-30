<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BuyOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Inventory extends Component
{
    public $filterItem   = '';
    public $filterTier   = '';
    public $filterStatus = '';
    public $activeTab    = 'stock';

    public function render()
    {
        $uid = Auth::id();

        // ── Raw per-order rows with computed columns ───────────────────────────
        $baseQuery = BuyOrder::where('buy_orders.user_id', $uid)
            ->select([
                'buy_orders.id',
                'buy_orders.order_id',
                'buy_orders.item_name',
                'buy_orders.tier',
                'buy_orders.enchantment',
                'buy_orders.qty_ordered',
                'buy_orders.qty_received',
                'buy_orders.qty_synced',
                'buy_orders.cost_per',
                'buy_orders.final_landed',
                'buy_orders.status',
                'buy_orders.ordered_date',
                DB::raw('COALESCE((
                    SELECT SUM(so.qty_sold) FROM sales_orders so
                    WHERE so.buy_order_id = buy_orders.id AND so.user_id = buy_orders.user_id
                ), 0) AS total_sold'),
                DB::raw('GREATEST(0, buy_orders.qty_synced - COALESCE((
                    SELECT SUM(so.qty_sold) FROM sales_orders so
                    WHERE so.buy_order_id = buy_orders.id AND so.user_id = buy_orders.user_id
                ), 0)) AS on_market'),
                DB::raw('GREATEST(0, buy_orders.qty_received - buy_orders.qty_synced - COALESCE((
                    SELECT SUM(so.qty_sold) FROM sales_orders so
                    WHERE so.buy_order_id = buy_orders.id AND so.user_id = buy_orders.user_id
                ), 0)) AS available'),
                DB::raw('CASE WHEN buy_orders.qty_ordered > 0
                    THEN buy_orders.final_landed / buy_orders.qty_ordered
                    ELSE 0 END AS landed_per_unit'),
            ]);

        // ── Out-of-stock ──────────────────────────────────────────────────────
        $oosBase = BuyOrder::where('user_id', $uid)
            ->whereIn('status', ['Received', 'Sold Out'])
            ->select([
                'item_name',
                'tier',
                'enchantment',
                DB::raw('GROUP_CONCAT(id) AS order_ids'),
                DB::raw('SUM(qty_ordered)  AS qty_ordered'),
                DB::raw('SUM(qty_received) AS qty_received'),
                DB::raw('SUM(qty_synced)   AS qty_synced'),
                DB::raw('SUM(final_landed) AS total_landed'),
                DB::raw('MAX(ordered_date) AS ordered_date'),
                DB::raw('MIN(DATEDIFF(completed_at, ordered_date)) AS days_to_sellout'),
            ])
            ->groupBy('item_name', 'tier', 'enchantment')
            ->havingRaw('SUM(qty_received) > 0')
            ->get();

        $allOosIds = $oosBase
            ->flatMap(fn($r) => explode(',', $r->order_ids))
            ->map(fn($id) => (int) $id);

        $soldSumsRaw = $allOosIds->isNotEmpty()
            ? DB::table('sales_orders')
                ->where('user_id', $uid)
                ->whereIn('buy_order_id', $allOosIds)
                ->select('buy_order_id', DB::raw('SUM(qty_sold) as qty_sold_sum'))
                ->groupBy('buy_order_id')
                ->pluck('qty_sold_sum', 'buy_order_id')
            : collect();

        $outOfStockRows = $oosBase->map(function ($row) use ($soldSumsRaw) {
            $ids       = explode(',', $row->order_ids);
            $totalSold = collect($ids)->sum(fn($id) => $soldSumsRaw[(int) $id] ?? 0);

            $row->total_sold      = $totalSold;
            $row->landed_per_unit = $row->qty_ordered > 0
                ? $row->total_landed / $row->qty_ordered
                : 0;

            return $row;
        })
        ->filter(fn($r) => $r->total_sold >= $r->qty_received)
        ->sortByDesc('total_sold')
        ->values();

        // ── Apply filters ─────────────────────────────────────────────────────
        $query = clone $baseQuery;

        $query->when($this->filterItem, fn($q) =>
            $q->where('buy_orders.item_name', 'like', '%' . $this->filterItem . '%')
        )
        ->when($this->filterTier, fn($q) =>
            $q->where('buy_orders.tier', $this->filterTier)
        );

        $rawRows = $query->orderByDesc('buy_orders.ordered_date')->get();

        // Hide fully sold rows — they belong in Out of Stock tab
        $rawRows = $rawRows->filter(
            fn($r) => $r->available > 0 || $r->on_market > 0 || $r->qty_received < $r->qty_ordered
        );

        // ── Stack rows by item_name + tier + enchantment (not status) ─────────
        $rows = $rawRows->groupBy(fn($r) =>
            $r->item_name . '||' . $r->tier . '||' . ($r->enchantment ?? '')
        )->map(function ($group) {
            $first    = $group->first();
            $statuses = $group->pluck('status')->unique()->values();

            // Pick most meaningful status for the stacked group
            if ($statuses->contains('Partial')) {
                $displayStatus = 'Partial';
            } elseif ($statuses->contains('Pending')) {
                $displayStatus = 'Pending';
            } elseif ($statuses->contains('Received')) {
                $displayStatus = 'Received';
            } else {
                $displayStatus = $first->status;
            }

            return (object) [
                'item_name'       => $first->item_name,
                'tier'            => $first->tier,
                'enchantment'     => $first->enchantment,
                'status'          => $displayStatus,
                'qty_ordered'     => $group->sum('qty_ordered'),
                'qty_received'    => $group->sum('qty_received'),
                'qty_synced'      => $group->sum('qty_synced'),
                'total_sold'      => $group->sum('total_sold'),
                'on_market'       => $group->sum('on_market'),
                'available'       => $group->sum('available'),
                'final_landed'    => $group->sum('final_landed'),
                'landed_per_unit' => $group->sum('qty_ordered') > 0
                    ? $group->sum('final_landed') / $group->sum('qty_ordered')
                    : 0,
                'order_count'     => $group->count(),
            ];
        })->values();

        // Apply status filter after stacking
        if ($this->filterStatus === 'available') {
            $rows = $rows->filter(fn($r) => $r->available > 0);
        } elseif ($this->filterStatus === 'on_market') {
            $rows = $rows->filter(fn($r) => $r->on_market > 0);
        }

        // ── Totals ────────────────────────────────────────────────────────────
        $totalAvailable  = $rows->sum('available');
        $totalOnMarket   = $rows->sum('on_market');
        $totalSold       = $rows->sum('total_sold');
        $totalValue      = $rows->sum(fn($r) => $r->available * $r->landed_per_unit);
        $totalOutOfStock = $outOfStockRows->count();

        return view('livewire.inventory', compact(
            'rows', 'outOfStockRows',
            'totalAvailable', 'totalOnMarket', 'totalSold', 'totalValue', 'totalOutOfStock'
        ));
    }
}