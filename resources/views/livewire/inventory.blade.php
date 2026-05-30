{{-- resources/views/livewire/inventory.blade.php --}}

<div class="bo-root">

{{-- ─── TOP BAR ─────────────────────────────────────────────────────────────── --}}
<div class="bo-topbar">
    <h1 class="bo-title">Inventory <span>Overview</span></h1>
</div>

{{-- ─── FILTERS ─────────────────────────────────────────────────────────────── --}}
<div class="bo-filters">
    <div class="bo-filter-group">
        <label for="inv-item">Item</label>
        <input id="inv-item" type="text" class="bo-filter-input"
            wire:model.live="filterItem" placeholder="Search item…">
    </div>
    <div class="bo-filter-group">
        <label for="inv-tier">Tier</label>
        <select id="inv-tier" class="bo-filter-input" wire:model.live="filterTier">
            <option value="">All tiers</option>
            @foreach(['T1','T2','T3','T4','T5','T6','T7','T8'] as $t)
                <option value="{{ $t }}">{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="bo-status-pills">
        <button class="bo-pill {{ $filterStatus === '' ? 'active' : '' }}"
            wire:click="$set('filterStatus', '')">All</button>
        <button class="bo-pill p-pending {{ $filterStatus === 'available' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'available')">Available</button>
        <button class="bo-pill p-partial {{ $filterStatus === 'on_market' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'on_market')">On Market</button>
    </div>
</div>

{{-- ─── STATS ───────────────────────────────────────────────────────────────── --}}
<div class="bo-stats">
    <div class="bo-stat">
        <div class="bo-stat-label">Available Stock</div>
        <div class="bo-stat-value accent">{{ number_format($totalAvailable) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">On Market</div>
        <div class="bo-stat-value" style="color:var(--gold)">{{ number_format($totalOnMarket) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Sold</div>
        <div class="bo-stat-value" style="color:var(--green)">{{ number_format($totalSold) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Stock Value</div>
        <div class="bo-stat-value gold">{{ number_format($totalValue, 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Out of Stock</div>
        <div class="bo-stat-value" style="color:{{ $totalOutOfStock > 0 ? 'var(--red)' : 'var(--text-3)' }}">
            {{ number_format($totalOutOfStock) }}
        </div>
    </div>
</div>

{{-- ─── TABS ─────────────────────────────────────────────────────────────────── --}}
<div class="inv-tabs">
    <button class="inv-tab {{ $activeTab === 'stock' ? 'active' : '' }}"
        wire:click="$set('activeTab', 'stock')">
        📦 In Stock
        @if($rows->count())
            <span class="inv-tab-count">{{ $rows->count() }}</span>
        @endif
    </button>
    <button class="inv-tab {{ $activeTab === 'oos' ? 'active' : '' }}"
        wire:click="$set('activeTab', 'oos')">
        ⚠️ Out of Stock
        @if($outOfStockRows->count())
            <span class="inv-tab-count oos">{{ $outOfStockRows->count() }}</span>
        @endif
    </button>
    <button class="inv-tab {{ $activeTab === 'insights' ? 'active' : '' }}"
        wire:click="$set('activeTab', 'insights')">
        🤖 AI Reorder Insights
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════════
     TAB: IN STOCK
═══════════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'stock')
<div class="bo-table-wrap">
    <table class="bo-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Tier</th>
                <th>Ench</th>
                <th>Orders</th>
                <th>Ordered</th>
                <th>Received</th>
                <th>Available</th>
                <th>On Market</th>
                <th>Sold</th>
                <th>Avg Cost / ea</th>
                <th>Stock Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php
                $bclass = match($row->status) {
                    'Pending'  => 'badge-pending',
                    'Partial'  => 'badge-partial',
                    'Received' => 'badge-received',
                    'Sold Out' => 'badge-soldout',
                    default    => 'badge-pending',
                };
                $blabel = match($row->status) {
                    'Pending'  => 'Ordering',
                    'Partial'  => 'Partial',
                    'Received' => 'Received',
                    'Sold Out' => 'Sold Out',
                    default    => 'Ordering',
                };
                $stockValue = $row->available * $row->landed_per_unit;
            @endphp
            <tr>
                <td class="strong">{{ $row->item_name }}</td>
                <td><span class="bo-tier">{{ $row->tier }}</span></td>
                <td>
                    @if($row->enchantment && $row->enchantment != '0')
                        <span class="bo-ench">{{ $row->enchantment }}</span>
                    @else
                        <span style="color:var(--text-3)">—</span>
                    @endif
                </td>
                <td class="mono" style="color:var(--text-3)">{{ $row->order_count }}</td>
                <td class="mono">{{ number_format($row->qty_ordered) }}</td>
                <td class="mono">{{ number_format($row->qty_received) }}</td>
                <td class="mono strong" style="color:{{ $row->available > 0 ? 'var(--accent)' : 'var(--text-3)' }}">
                    {{ number_format($row->available) }}
                </td>
                <td class="mono" style="color:var(--gold)">{{ number_format($row->on_market) }}</td>
                <td class="mono" style="color:var(--green)">{{ number_format($row->total_sold) }}</td>
                <td class="mono" style="color:var(--gold)">{{ number_format($row->landed_per_unit, 2) }}s</td>
                <td class="mono">{{ number_format($stockValue, 0) }}s</td>
                <td><span class="bo-badge {{ $bclass }}">{{ $blabel }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="12">
                    <div class="bo-empty">
                        <div class="bo-empty-icon">📦</div>
                        No inventory found. Receive some buy orders first.
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════════
     TAB: OUT OF STOCK
═══════════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'oos')
<div class="bo-table-wrap">
    <table class="bo-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Tier</th>
                <th>Ench</th>
                <th>Total Ordered</th>
                <th>Total Sold</th>
                <th>Avg Cost / ea</th>
                <th>Last Ordered</th>
                <th>Sell-Through</th>
                <th>Signal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($outOfStockRows as $row)
            @php
                $sellThrough = $row->qty_ordered > 0
                    ? ($row->total_sold / $row->qty_ordered) * 100
                    : 0;

                if ($row->days_to_sellout !== null && $row->days_to_sellout <= 1) {
                    $signal = ['label' => '🔥 Hot', 'class' => 'badge-received', 'tip' => 'Sold out within 1 day — reorder immediately'];
                } elseif ($row->days_to_sellout !== null && $row->days_to_sellout <= 3) {
                    $signal = ['label' => '📈 Fast', 'class' => 'badge-partial', 'tip' => 'Sold out in 2–3 days — worth restocking'];
                } elseif ($sellThrough >= 80) {
                    $signal = ['label' => '✅ Good', 'class' => 'badge-received', 'tip' => 'High sell-through — consider reordering'];
                } elseif ($sellThrough >= 40) {
                    $signal = ['label' => '⚖️ OK', 'class' => 'badge-pending', 'tip' => 'Moderate sell-through — monitor before reordering'];
                } else {
                    $signal = ['label' => '❄️ Slow', 'class' => 'badge-soldout', 'tip' => 'Low sell-through — avoid restocking for now'];
                }
            @endphp
            <tr>
                <td class="strong">{{ $row->item_name }}</td>
                <td><span class="bo-tier">{{ $row->tier }}</span></td>
                <td>
                    @if($row->enchantment && $row->enchantment != '0')
                        <span class="bo-ench">{{ $row->enchantment }}</span>
                    @else
                        <span style="color:var(--text-3)">—</span>
                    @endif
                </td>
                <td class="mono">{{ number_format($row->qty_ordered) }}</td>
                <td class="mono" style="color:var(--green)">{{ number_format($row->total_sold) }}</td>
                <td class="mono" style="color:var(--gold)">{{ number_format($row->landed_per_unit, 2) }}s</td>
                <td class="mono" style="color:var(--text-3)">
                    {{ $row->ordered_date ? \Carbon\Carbon::parse($row->ordered_date)->format('d M Y') : '—' }}
                </td>
                <td class="mono">
                    <div class="inv-bar-wrap">
                        <div class="inv-bar" style="width:{{ min($sellThrough, 100) }}%;background:{{ $sellThrough >= 80 ? 'var(--green)' : ($sellThrough >= 40 ? 'var(--gold)' : 'var(--red)') }}"></div>
                        <span>{{ number_format($sellThrough, 0) }}%</span>
                    </div>
                </td>
                <td>
                    <span class="bo-badge {{ $signal['class'] }}" title="{{ $signal['tip'] }}">
                        {{ $signal['label'] }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9">
                    <div class="bo-empty">
                        <div class="bo-empty-icon">✅</div>
                        All items have stock available. Nothing is out of stock.
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════════
     TAB: AI REORDER INSIGHTS
═══════════════════════════════════════════════════════════════════════════════ --}}
@if($activeTab === 'insights')
<div class="inv-insights">

    @php
        $aiItems = $outOfStockRows->map(fn($r) => [
            'item'         => $r->item_name,
            'tier'         => $r->tier,
            'sold'         => $r->total_sold,
            'ordered'      => $r->qty_ordered,
            'sell_through' => $r->qty_ordered > 0 ? round(($r->total_sold / $r->qty_ordered) * 100) : 0,
            'days_to_sell' => $r->days_to_sellout,
            'last_cost'    => round($r->landed_per_unit, 2),
        ])->values()->toArray();

        $inStockItems = $rows->map(fn($r) => [
            'item'      => $r->item_name,
            'tier'      => $r->tier,
            'available' => $r->available,
            'on_market' => $r->on_market,
            'sold'      => $r->total_sold,
            'last_cost' => round($r->landed_per_unit, 2),
        ])->values()->toArray();
    @endphp

    <div class="inv-ai-card" id="inv-ai-card">
        <div class="inv-ai-header">
            <div class="inv-ai-title">
                <span class="inv-ai-icon">🤖</span>
                AI Reorder Analysis
            </div>
            <button class="bo-btn-primary" id="inv-ai-btn"
                onclick="runAiInsights({{ json_encode($aiItems) }}, {{ json_encode($inStockItems) }})">
                ✨ Analyse My Inventory
            </button>
        </div>
        <div class="inv-ai-desc">
            Claude will analyse your sell-through rates, speed, and stock levels to recommend
            what to reorder, what to avoid, and what to watch.
        </div>
        <div id="inv-ai-output" class="inv-ai-output" style="display:none"></div>
        <div id="inv-ai-loading" class="inv-ai-loading" style="display:none">
            <div class="inv-ai-spinner"></div>
            Analysing your inventory data…
        </div>
    </div>

    <div class="inv-legend">
        <div class="inv-legend-title">Signal Guide</div>
        <div class="inv-legend-items">
            <div class="inv-legend-item">
                <span class="bo-badge badge-received">🔥 Hot</span>
                <span>Sold out ≤ 1 day — reorder urgently</span>
            </div>
            <div class="inv-legend-item">
                <span class="bo-badge badge-partial">📈 Fast</span>
                <span>Sold out in 2–3 days — worth restocking</span>
            </div>
            <div class="inv-legend-item">
                <span class="bo-badge badge-received">✅ Good</span>
                <span>80%+ sell-through — consider reordering</span>
            </div>
            <div class="inv-legend-item">
                <span class="bo-badge badge-pending">⚖️ OK</span>
                <span>40–79% sell-through — monitor first</span>
            </div>
            <div class="inv-legend-item">
                <span class="bo-badge badge-soldout">❄️ Slow</span>
                <span>&lt;40% sell-through — avoid restocking</span>
            </div>
        </div>
    </div>

</div>
@endif

</div>

{{-- ─── STYLES ───────────────────────────────────────────────────────────────── --}}
<style>
.inv-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--border);
    padding-bottom: 0;
}
.inv-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px 10px;
    border: none;
    background: transparent;
    color: var(--text-3);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: color 0.15s, border-color 0.15s;
}
.inv-tab:hover { color: var(--text-1); }
.inv-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
}
.inv-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 9px;
    background: var(--surface-2, rgba(255,255,255,0.08));
    color: var(--text-2);
    font-size: 0.72rem;
    font-weight: 600;
}
.inv-tab-count.oos {
    background: rgba(248,113,113,0.18);
    color: var(--red);
}
.inv-bar-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 110px;
}
.inv-bar-wrap > span {
    font-size: 0.8rem;
    color: var(--text-2);
    min-width: 36px;
}
.inv-bar {
    height: 6px;
    border-radius: 3px;
    flex: 1;
    min-width: 4px;
    transition: width 0.3s;
}
.inv-insights { display: flex; flex-direction: column; gap: 16px; }
.inv-ai-card {
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 22px 24px;
}
.inv-ai-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    gap: 12px;
    flex-wrap: wrap;
}
.inv-ai-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-1);
}
.inv-ai-icon { font-size: 1.3rem; }
.inv-ai-desc {
    color: var(--text-3);
    font-size: 0.85rem;
    margin-bottom: 16px;
    line-height: 1.5;
}
.inv-ai-output {
    color: var(--text-1);
    font-size: 0.875rem;
    line-height: 1.7;
    white-space: pre-wrap;
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: 4px;
}
.inv-ai-output h3 {
    color: var(--accent);
    font-size: 0.9rem;
    font-weight: 600;
    margin: 14px 0 6px;
}
.inv-ai-output ul { padding-left: 18px; margin: 6px 0; }
.inv-ai-output li { margin-bottom: 4px; }
.inv-ai-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-3);
    font-size: 0.85rem;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    margin-top: 4px;
}
.inv-ai-spinner {
    width: 16px; height: 16px;
    border: 2px solid var(--border);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.inv-legend {
    background: var(--surface-1);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 22px;
}
.inv-legend-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 12px;
}
.inv-legend-items { display: flex; flex-direction: column; gap: 8px; }
.inv-legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.84rem;
    color: var(--text-2);
}
</style>

{{-- ─── AI SCRIPT ────────────────────────────────────────────────────────────── --}}
<script>
async function runAiInsights(outOfStock, inStock) {
    const btn     = document.getElementById('inv-ai-btn');
    const output  = document.getElementById('inv-ai-output');
    const loading = document.getElementById('inv-ai-loading');

    btn.disabled = true;
    btn.textContent = '⏳ Analysing…';
    output.style.display  = 'none';
    loading.style.display = 'flex';

    const prompt = `You are an Albion Online market trading assistant. Analyse this player's inventory data and give concise, practical reorder recommendations.

OUT OF STOCK items (sold out, need decision to reorder or not):
${JSON.stringify(outOfStock, null, 2)}

CURRENTLY IN STOCK items (for context):
${JSON.stringify(inStock, null, 2)}

Fields explained:
- sell_through: % of ordered qty that was sold (100% = all sold)
- days_to_sell: how many days it took to sell out (null = unknown)
- last_cost: landed cost per unit in silver

Respond in this exact format (use ### for section headers):

### 🔥 Reorder Now
List items that sold fast or have high sell-through. For each: item name, tier, why, suggested reorder qty (relative to what sold).

### ⚖️ Monitor First
Items with moderate sell-through. Brief reason.

### ❌ Avoid Restocking
Items with low sell-through or slow sales. Brief reason.

### 💡 General Advice
2-3 short bullet points about their overall inventory strategy based on the data.

Keep it concise and actionable. Use silver (s) for prices.`;

    try {
        const res = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: 'claude-sonnet-4-20250514',
                max_tokens: 1000,
                messages: [{ role: 'user', content: prompt }]
            })
        });

        const data = await res.json();
        const text = data.content?.[0]?.text ?? 'No response received.';

        const html = text
            .replace(/### (.+)/g, '<h3>$1</h3>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/^- (.+)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>')
            .replace(/\n{2,}/g, '\n');

        output.innerHTML = html;
        output.style.display = 'block';
    } catch (err) {
        output.innerHTML = '<span style="color:var(--red)">Failed to get AI response. Please try again.</span>';
        output.style.display = 'block';
    } finally {
        loading.style.display = 'none';
        btn.disabled = false;
        btn.textContent = '✨ Analyse Again';
    }
}
</script>