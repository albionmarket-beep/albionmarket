{{-- resources/views/livewire/dashboard.blade.php --}}

<div class="bo-root">

{{-- ─── TOP BAR ─────────────────────────────────────────────────────────────── --}}
<div class="bo-topbar">
    <h1 class="bo-title">Dashboard <span>Overview</span></h1>
    <div class="bo-filter-group" style="margin:0">
        <label for="dash-date" style="color:var(--text-3);font-size:0.8rem">Date</label>
        <input
            id="dash-date"
            type="date"
            class="bo-filter-input"
            wire:model.live="filterDate"
        >
    </div>
</div>

{{-- ─── KPI STATS ───────────────────────────────────────────────────────────── --}}
<div class="dash-stats-grid">
    <div class="bo-stat">
        <div class="bo-stat-label">Revenue</div>
        <div class="bo-stat-value gold">{{ number_format($summary['total_revenue'], 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Profit</div>
        <div class="bo-stat-value" style="color:{{ $summary['total_profit'] >= 0 ? 'var(--green)' : 'var(--red)' }}">
            {{ number_format($summary['total_profit'], 0) }}s
        </div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Margin</div>
        <div class="bo-stat-value" style="color:{{ $summary['margin'] >= 0 ? 'var(--green)' : 'var(--red)' }}">
            {{ number_format($summary['margin'], 1) }}%
        </div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Units Sold</div>
        <div class="bo-stat-value accent">{{ number_format($summary['total_sold']) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Buy Spend</div>
        <div class="bo-stat-value" style="color:var(--red)">{{ number_format($summary['total_spend'], 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Fees Paid</div>
        <div class="bo-stat-value" style="color:var(--red)">
            {{ number_format($summary['total_fees_paid'], 0) }}s
        </div>
        <div class="bo-stat-hint">
            Buy: {{ number_format($summary['buy_fees_paid'], 0) }}s
            &nbsp;·&nbsp;
            Sell: {{ number_format($summary['sell_fees_paid'], 0) }}s
        </div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Buy Orders</div>
        <div class="bo-stat-value">{{ number_format($summary['buy_order_count']) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Pending Buys</div>
        <div class="bo-stat-value" style="color:{{ $summary['pending_buy_orders'] > 0 ? 'var(--gold)' : 'var(--text-3)' }}">
            {{ number_format($summary['pending_buy_orders']) }}
        </div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Sales Orders</div>
        <div class="bo-stat-value">{{ number_format($summary['total_orders']) }}</div>
    </div>
</div>

{{-- ─── MAIN GRID ───────────────────────────────────────────────────────────── --}}
<div class="dash-grid">

    {{-- Top Items by Profit --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title">🏆 Top Items by Profit</span>
            <span class="dash-card-sub">Today's best performers</span>
        </div>
        @if($topItems->isEmpty())
            <div class="bo-empty" style="padding:24px 0">
                <div class="bo-empty-icon">📊</div>
                No sales recorded yet today.
            </div>
        @else
        <div class="dash-table-wrap">
            <table class="bo-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tier</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Fees Paid</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topItems as $item)
                    <tr>
                        <td class="strong">{{ $item->item_name }}</td>
                        <td><span class="bo-tier">{{ $item->tier }}</span></td>
                        <td class="mono">{{ number_format($item->total_sold) }}</td>
                        <td class="mono" style="color:var(--gold)">{{ number_format($item->total_rev, 0) }}s</td>
                        <td class="mono" style="color:var(--red)">{{ number_format($item->total_fees, 0) }}s</td>
                        <td class="mono strong" style="color:{{ $item->total_profit >= 0 ? 'var(--green)' : 'var(--red)' }}">
                            {{ number_format($item->total_profit, 0) }}s
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Recent Buy Orders --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title">📦 Recent Buy Orders</span>
            <a href="{{ route('buy-order') }}" class="dash-card-link">View all →</a>
        </div>
        @if($recentBuyOrders->isEmpty())
            <div class="bo-empty" style="padding:24px 0">
                <div class="bo-empty-icon">📦</div>
                No buy orders today.
            </div>
        @else
        <div class="dash-table-wrap">
            <table class="bo-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tier</th>
                        <th>Qty</th>
                        <th>Landed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBuyOrders as $order)
                    @php
                        $bclass = match($order->status) {
                            'Pending'  => 'badge-pending',
                            'Partial'  => 'badge-partial',
                            'Received' => 'badge-received',
                            'Sold Out' => 'badge-soldout',
                            default    => 'badge-pending',
                        };
                    @endphp
                    <tr>
                        <td class="strong">{{ $order->item_name }}</td>
                        <td><span class="bo-tier">{{ $order->tier }}</span></td>
                        <td class="mono">{{ number_format($order->qty_ordered) }}</td>
                        <td class="mono" style="color:var(--gold)">{{ number_format($order->final_landed, 0) }}s</td>
                        <td><span class="bo-badge {{ $bclass }}">{{ $order->status }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Recent Sales --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <span class="dash-card-title">🛒 Recent Sales</span>
            <a href="{{ route('sales-order') }}" class="dash-card-link">View all →</a>
        </div>
        @if($recentSales->isEmpty())
            <div class="bo-empty" style="padding:24px 0">
                <div class="bo-empty-icon">🛒</div>
                No sales recorded today.
            </div>
        @else
        <div class="dash-table-wrap">
            <table class="bo-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tier</th>
                        <th>Sold</th>
                        <th>Profit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $order)
                    @php
                        $sclass = match($order->status) {
                            'Pending'  => 'badge-pending',
                            'Partial'  => 'badge-partial',
                            'Complete' => 'badge-received',
                            default    => 'badge-pending',
                        };
                    @endphp
                    <tr>
                        <td class="strong">{{ $order->item_name }}</td>
                        <td><span class="bo-tier">{{ $order->tier }}</span></td>
                        <td class="mono">{{ number_format($order->qty_sold) }}</td>
                        <td class="mono strong" style="color:{{ $order->profit >= 0 ? 'var(--green)' : 'var(--red)' }}">
                            {{ number_format($order->profit, 0) }}s
                        </td>
                        <td><span class="bo-badge {{ $sclass }}">{{ $order->status }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>

</div>

<style>
/* ── Stats grid: wraps cleanly, no overflow ── */
.dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

/* ── Main cards grid ── */
.dash-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
    margin-top: 4px;
}
.dash-card {
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 22px;
    /* Prevent card content from overflowing / overlapping siblings */
    min-width: 0;
    overflow: hidden;
}
.dash-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.dash-card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-1);
}
.dash-card-sub {
    font-size: 0.78rem;
    color: var(--text-3);
}
.dash-card-link {
    font-size: 0.78rem;
    color: var(--accent);
    text-decoration: none;
    white-space: nowrap;
}
.dash-card-link:hover { text-decoration: underline; }

/* ── Table wrapper inside cards: scrollable, no bleed ── */
.dash-table-wrap {
    overflow-x: auto;
    margin-top: 8px;
    /* Ensure table doesn't push card wider than viewport */
    max-width: 100%;
}

/* ── Small hint text under a stat value ── */
.bo-stat-hint {
    font-size: 10px;
    color: var(--text-3, #666);
    margin-top: 2px;
    opacity: 0.7;
}

/* ── Mobile: single column ── */
@media (max-width: 640px) {
    .dash-grid {
        grid-template-columns: 1fr;
    }
    .dash-stats-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    }
}
</style>