<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SalesOrder;
use App\Models\BuyOrder;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $filterDate;

    public function mount()
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function getSummaryProperty()
    {
        $uid = Auth::id();

        $sales = SalesOrder::where('user_id', $uid)
            ->whereDate('listed_date', $this->filterDate)
            ->selectRaw('
                COALESCE(SUM(total_rev), 0)                    AS total_revenue,
                COALESCE(SUM(total_cost), 0)                   AS total_cost,
                COALESCE(SUM(profit), 0)                       AS total_profit,
                COALESCE(SUM(qty_sold), 0)                     AS total_sold,
                COUNT(*)                                        AS total_orders,
                COALESCE(SUM((premium_tax + setup_fee) * qty), 0) AS total_fees_paid
            ')
            ->first();

        $buyStats = BuyOrder::where('user_id', $uid)
            ->whereDate('ordered_date', $this->filterDate)
            ->selectRaw('
                COALESCE(SUM(final_landed), 0) AS total_spend,
                COALESCE(SUM(total_setup), 0)  AS buy_fees_paid,
                COUNT(*)                        AS buy_order_count
            ')
            ->first();

        $pendingBuyOrders = BuyOrder::where('user_id', $uid)
            ->whereDate('ordered_date', $this->filterDate)
            ->where('status', 'Pending')
            ->count();

        $margin = ($sales->total_revenue ?? 0) > 0
            ? (($sales->total_profit ?? 0) / $sales->total_revenue) * 100
            : 0;

        $totalFeesPaid = ($sales->total_fees_paid ?? 0) + ($buyStats->buy_fees_paid ?? 0);

        return [
            'total_revenue'      => $sales->total_revenue        ?? 0,
            'total_cost'         => $sales->total_cost           ?? 0,
            'total_profit'       => $sales->total_profit         ?? 0,
            'total_sold'         => $sales->total_sold           ?? 0,
            'total_orders'       => $sales->total_orders         ?? 0,
            'total_spend'        => $buyStats->total_spend       ?? 0,
            'buy_order_count'    => $buyStats->buy_order_count   ?? 0,
            'pending_buy_orders' => $pendingBuyOrders,
            'margin'             => $margin,
            'total_fees_paid'    => $totalFeesPaid,
            'buy_fees_paid'      => $buyStats->buy_fees_paid     ?? 0,
            'sell_fees_paid'     => $sales->total_fees_paid      ?? 0,
        ];
    }

    public function getTopItemsProperty()
    {
        return SalesOrder::where('user_id', Auth::id())
            ->whereDate('listed_date', $this->filterDate)
            ->groupBy('item_name', 'tier')
            ->selectRaw('
                item_name,
                tier,
                SUM(qty_sold)                              AS total_sold,
                SUM(total_rev)                             AS total_rev,
                SUM(profit)                                AS total_profit,
                AVG(sell_price)                            AS avg_sell,
                SUM((premium_tax + setup_fee) * qty)       AS total_fees
            ')
            ->orderByDesc('total_profit')
            ->limit(5)
            ->get();
    }

    public function getRecentBuyOrdersProperty()
    {
        return BuyOrder::where('user_id', Auth::id())
            ->whereDate('ordered_date', $this->filterDate)
            ->latest('ordered_at')
            ->limit(5)
            ->get();
    }

    public function getRecentSalesProperty()
    {
        return SalesOrder::where('user_id', Auth::id())
            ->whereDate('listed_date', $this->filterDate)
            ->latest('listed_date')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'summary'         => $this->summary,
            'topItems'        => $this->topItems,
            'recentBuyOrders' => $this->recentBuyOrders,
            'recentSales'     => $this->recentSales,
        ]);
    }
}