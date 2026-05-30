<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SalesOrder;
use App\Models\BuyOrder;
use Illuminate\Support\Facades\Auth;

class Reports extends Component
{
    public $rangeFrom;
    public $rangeTo;
    public $groupBy = 'day'; // day | item | tier

    public function mount()
    {
        $this->rangeFrom = now()->startOfMonth()->format('Y-m-d');
        $this->rangeTo   = now()->format('Y-m-d');
    }

    // ── Summary KPIs ──────────────────────────────────────────────────────────
    public function getSummaryProperty()
    {
        $uid = Auth::id();

        $sales = SalesOrder::where('user_id', $uid)
            ->whereBetween('listed_date', [$this->rangeFrom, $this->rangeTo])
            ->selectRaw('
                COALESCE(SUM(total_rev), 0)                       AS total_revenue,
                COALESCE(SUM(total_cost), 0)                      AS total_cost,
                COALESCE(SUM(profit), 0)                          AS total_profit,
                COALESCE(SUM(qty_sold), 0)                        AS total_sold,
                COALESCE(SUM((premium_tax + setup_fee) * qty), 0) AS total_fees_paid
            ')
            ->first();

        $buyStats = BuyOrder::where('user_id', $uid)
            ->whereBetween('ordered_date', [$this->rangeFrom, $this->rangeTo])
            ->selectRaw('
                COALESCE(SUM(final_landed), 0) AS total_spend,
                COALESCE(SUM(total_setup), 0)  AS buy_fees_paid
            ')
            ->first();

        $margin = ($sales->total_revenue ?? 0) > 0
            ? (($sales->total_profit ?? 0) / $sales->total_revenue) * 100
            : 0;

        $totalFeesPaid = ($sales->total_fees_paid ?? 0) + ($buyStats->buy_fees_paid ?? 0);

        return [
            'total_revenue'   => $sales->total_revenue       ?? 0,
            'total_cost'      => $sales->total_cost          ?? 0,
            'total_profit'    => $sales->total_profit        ?? 0,
            'total_sold'      => $sales->total_sold          ?? 0,
            'total_spend'     => $buyStats->total_spend      ?? 0,
            'margin'          => $margin,
            'total_fees_paid' => $totalFeesPaid,
            'buy_fees_paid'   => $buyStats->buy_fees_paid    ?? 0,
            'sell_fees_paid'  => $sales->total_fees_paid     ?? 0,
        ];
    }

    // ── Top items by profit ───────────────────────────────────────────────────
    public function getTopItemsProperty()
    {
        return SalesOrder::where('user_id', Auth::id())
            ->whereBetween('listed_date', [$this->rangeFrom, $this->rangeTo])
            ->groupBy('item_name', 'tier')
            ->selectRaw('
                item_name,
                tier,
                SUM(qty_sold)                              AS total_sold,
                SUM(total_rev)                             AS total_rev,
                SUM(total_cost)                            AS total_cost,
                SUM(profit)                                AS total_profit,
                AVG(sell_price)                            AS avg_sell,
                SUM((premium_tax + setup_fee) * qty)       AS total_fees
            ')
            ->orderByDesc('total_profit')
            ->limit(10)
            ->get();
    }

    // ── Chart data: profit over time / per item / per tier ────────────────────
    public function getChartDataProperty()
    {
        $uid = Auth::id();

        if ($this->groupBy === 'item') {
            return SalesOrder::where('user_id', $uid)
                ->whereBetween('listed_date', [$this->rangeFrom, $this->rangeTo])
                ->groupBy('item_name')
                ->selectRaw('
                    item_name                                  AS label,
                    SUM(profit)                                AS profit,
                    SUM(total_rev)                             AS revenue,
                    SUM((premium_tax + setup_fee) * qty)       AS fees
                ')
                ->orderByDesc('profit')
                ->limit(12)
                ->get();
        }

        if ($this->groupBy === 'tier') {
            return SalesOrder::where('user_id', $uid)
                ->whereBetween('listed_date', [$this->rangeFrom, $this->rangeTo])
                ->groupBy('tier')
                ->selectRaw('
                    tier                                       AS label,
                    SUM(profit)                                AS profit,
                    SUM(total_rev)                             AS revenue,
                    SUM((premium_tax + setup_fee) * qty)       AS fees
                ')
                ->orderBy('tier')
                ->get();
        }

        // default: day
        return SalesOrder::where('user_id', $uid)
            ->whereBetween('listed_date', [$this->rangeFrom, $this->rangeTo])
            ->groupBy('listed_date')
            ->selectRaw('
                DATE_FORMAT(listed_date, "%d %b")          AS label,
                SUM(profit)                                AS profit,
                SUM(total_rev)                             AS revenue,
                SUM((premium_tax + setup_fee) * qty)       AS fees
            ')
            ->orderBy('listed_date')
            ->get();
    }

    // ── Buy spend over time ───────────────────────────────────────────────────
    public function getSpendDataProperty()
    {
        return BuyOrder::where('user_id', Auth::id())
            ->whereBetween('ordered_date', [$this->rangeFrom, $this->rangeTo])
            ->groupBy('ordered_date')
            ->selectRaw('DATE_FORMAT(ordered_date, "%d %b") AS label, SUM(final_landed) AS spend')
            ->orderBy('ordered_date')
            ->get();
    }

    public function render()
    {
        return view('livewire.reports', [
            'summary'   => $this->summary,
            'topItems'  => $this->topItems,
            'chartData' => $this->chartData,
            'spendData' => $this->spendData,
        ]);
    }
}