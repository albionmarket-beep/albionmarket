{{-- resources/views/livewire/reports.blade.php --}}
{{--
    Requires Chart.js — add to your layout:
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
--}}

<div class="bo-root" x-data>

{{-- ─── TOP BAR ─────────────────────────────────────────────────────────────── --}}
<div class="bo-topbar">
    <h1 class="bo-title">Reports <span>&amp; Analytics</span></h1>
</div>

{{-- ─── DATE RANGE + GROUP BY ───────────────────────────────────────────────── --}}
<div class="bo-filters" style="flex-wrap:wrap;gap:12px 20px">
    <div class="bo-filter-group">
        <label for="rpt-from">From</label>
        <input id="rpt-from" type="date" class="bo-filter-input" wire:model.live="rangeFrom">
    </div>
    <div class="bo-filter-group">
        <label for="rpt-to">To</label>
        <input id="rpt-to" type="date" class="bo-filter-input" wire:model.live="rangeTo">
    </div>
    <div class="bo-status-pills">
        <button class="bo-pill {{ $groupBy === 'day'  ? 'active' : '' }}" wire:click="$set('groupBy','day')">By Day</button>
        <button class="bo-pill {{ $groupBy === 'item' ? 'active' : '' }}" wire:click="$set('groupBy','item')">By Item</button>
        <button class="bo-pill {{ $groupBy === 'tier' ? 'active' : '' }}" wire:click="$set('groupBy','tier')">By Tier</button>
    </div>
</div>

{{-- ─── KPI CARDS ───────────────────────────────────────────────────────────── --}}
<div class="rpt-stats-grid">
    <div class="bo-stat">
        <div class="bo-stat-label">Revenue</div>
        <div class="bo-stat-value gold">{{ number_format($summary['total_revenue'], 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Cost of Goods</div>
        <div class="bo-stat-value">{{ number_format($summary['total_cost'], 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Gross Profit</div>
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
        <div class="bo-stat-value" style="color:var(--text-2)">{{ number_format($summary['total_spend'], 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Fees Paid</div>
        <div class="bo-stat-value" style="color:var(--red)">{{ number_format($summary['total_fees_paid'], 0) }}s</div>
        <div class="bo-stat-hint">
            Buy: {{ number_format($summary['buy_fees_paid'], 0) }}s
            &nbsp;·&nbsp;
            Sell: {{ number_format($summary['sell_fees_paid'], 0) }}s
        </div>
    </div>
</div>

{{-- ─── CHARTS ──────────────────────────────────────────────────────────────── --}}
<div class="rpt-charts-grid">

    {{-- Profit / Revenue / Fees chart --}}
    <div class="rpt-card">
        <div class="rpt-card-title">
            Profit, Revenue &amp; Fees
            <span style="color:var(--text-3);font-size:0.8rem;font-weight:400;margin-left:6px">
                grouped by {{ $groupBy }}
            </span>
        </div>
        <div class="rpt-chart-wrap">
            <canvas id="rptProfitChart"
                data-labels="{{ json_encode($chartData->pluck('label')) }}"
                data-profit="{{ json_encode($chartData->pluck('profit')) }}"
                data-revenue="{{ json_encode($chartData->pluck('revenue')) }}"
                data-fees="{{ json_encode($chartData->pluck('fees')) }}"
                wire:ignore
            ></canvas>
        </div>
    </div>

    {{-- Buy Spend chart --}}
    <div class="rpt-card">
        <div class="rpt-card-title">
            Buy Spend
            <span style="color:var(--text-3);font-size:0.8rem;font-weight:400;margin-left:6px">per day</span>
        </div>
        <div class="rpt-chart-wrap">
            <canvas id="rptSpendChart"
                data-labels="{{ json_encode($spendData->pluck('label')) }}"
                data-spend="{{ json_encode($spendData->pluck('spend')) }}"
                wire:ignore
            ></canvas>
        </div>
    </div>

</div>

{{-- ─── TOP ITEMS TABLE ─────────────────────────────────────────────────────── --}}
<div class="rpt-card" style="margin-bottom:8px">
    <div class="rpt-card-title">Top Items by Profit</div>
    <div class="bo-table-wrap" style="margin-top:12px">
        <table class="bo-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Tier</th>
                    <th>Sold</th>
                    <th>Revenue</th>
                    <th>Cost</th>
                    <th>Fees Paid</th>
                    <th>Profit</th>
                    <th>Margin</th>
                    <th>Avg Sell</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topItems as $i => $item)
                @php $m = $item->total_rev > 0 ? ($item->total_profit / $item->total_rev) * 100 : 0; @endphp
                <tr>
                    <td class="mono" style="color:var(--text-3)">{{ $i + 1 }}</td>
                    <td class="strong">{{ $item->item_name }}</td>
                    <td><span class="bo-tier">{{ $item->tier }}</span></td>
                    <td class="mono">{{ number_format($item->total_sold) }}</td>
                    <td class="mono" style="color:var(--gold)">{{ number_format($item->total_rev, 0) }}s</td>
                    <td class="mono" style="color:var(--text-3)">{{ number_format($item->total_cost, 0) }}s</td>
                    <td class="mono" style="color:var(--red)">{{ number_format($item->total_fees, 0) }}s</td>
                    <td class="mono strong" style="color:{{ $item->total_profit >= 0 ? 'var(--green)' : 'var(--red)' }}">
                        {{ number_format($item->total_profit, 0) }}s
                    </td>
                    <td class="mono" style="color:{{ $m >= 0 ? 'var(--green)' : 'var(--red)' }}">
                        {{ number_format($m, 1) }}%
                    </td>
                    <td class="mono">{{ number_format($item->avg_sell, 2) }}s</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="bo-empty">
                            <div class="bo-empty-icon">📊</div>
                            No sales data for this period.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>

{{-- ─── STYLES ──────────────────────────────────────────────────────────────── --}}
<style>
.rpt-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.rpt-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}
.rpt-card {
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 18px 20px;
    min-width: 0;
    overflow: hidden;
}
.rpt-card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-1);
    margin-bottom: 4px;
}
/* Fixed-height chart wrapper so canvas never overflows */
.rpt-chart-wrap {
    position: relative;
    height: 260px;
    width: 100%;
}
.rpt-chart-wrap canvas {
    position: absolute;
    inset: 0;
    width: 100% !important;
    height: 100% !important;
}
.bo-stat-hint {
    font-size: 10px;
    color: var(--text-3, #666);
    margin-top: 2px;
    opacity: 0.7;
}
@media (max-width: 640px) {
    .rpt-charts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

{{-- ─── CHART SCRIPTS ────────────────────────────────────────────────────────── --}}
<script>
(function () {
    let profitChart = null;
    let spendChart  = null;

    function hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1,3),16);
        const g = parseInt(hex.slice(3,5),16);
        const b = parseInt(hex.slice(5,7),16);
        return `rgba(${r},${g},${b},${alpha})`;
    }

    function buildProfitChart() {
        const el = document.getElementById('rptProfitChart');
        if (!el) return;
        const labels  = JSON.parse(el.dataset.labels  || '[]');
        const profit  = JSON.parse(el.dataset.profit  || '[]');
        const revenue = JSON.parse(el.dataset.revenue || '[]');
        const fees    = JSON.parse(el.dataset.fees    || '[]');

        if (profitChart) { profitChart.destroy(); profitChart = null; }
        profitChart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenue,
                        backgroundColor: hexToRgba('#c89b3c', 0.35),
                        borderColor: '#c89b3c',
                        borderWidth: 1.5,
                        borderRadius: 4,
                        order: 3,
                    },
                    {
                        label: 'Fees Paid',
                        data: fees,
                        backgroundColor: hexToRgba('#f87171', 0.5),
                        borderColor: '#f87171',
                        borderWidth: 1.5,
                        borderRadius: 4,
                        order: 2,
                    },
                    {
                        label: 'Profit',
                        data: profit,
                        type: 'line',
                        borderColor: '#4ade80',
                        backgroundColor: hexToRgba('#4ade80', 0.15),
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: true,
                        tension: 0.3,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#9ca3af', font: { size: 11 } } } },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
                    y: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.06)' } },
                },
            },
        });
    }

    function buildSpendChart() {
        const el = document.getElementById('rptSpendChart');
        if (!el) return;
        const labels = JSON.parse(el.dataset.labels || '[]');
        const spend  = JSON.parse(el.dataset.spend  || '[]');

        if (spendChart) { spendChart.destroy(); spendChart = null; }
        spendChart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Buy Spend',
                    data: spend,
                    backgroundColor: hexToRgba('#818cf8', 0.4),
                    borderColor: '#818cf8',
                    borderWidth: 1.5,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#9ca3af', font: { size: 11 } } } },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
                    y: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.06)' } },
                },
            },
        });
    }

    function buildAll() {
        if (typeof Chart === 'undefined') return;
        buildProfitChart();
        buildSpendChart();
    }

    document.addEventListener('DOMContentLoaded', buildAll);
    document.addEventListener('livewire:navigated', function () { setTimeout(buildAll, 50); });

    if (window.Livewire) {
        Livewire.hook('morph.updated', function () { setTimeout(buildAll, 50); });
    }
})();
</script>