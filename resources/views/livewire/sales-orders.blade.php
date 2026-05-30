{{-- resources/views/livewire/sales-orders.blade.php --}}
{{-- Styles: public/css/buy-orders.css (reuses same design tokens) --}}

<div class="bo-root">

{{-- ─── TOP BAR ─────────────────────────────────────────────────────────────── --}}
<div class="bo-topbar">
    <h1 class="bo-title">Sales <span>Orders</span></h1>
    <button class="bo-btn-primary" wire:click="openModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Add Sales Order
    </button>
</div>

{{-- ─── FILTERS ─────────────────────────────────────────────────────────────── --}}
<div class="bo-filters">
    <div class="bo-filter-group">
        <label for="so-date-filter">Date</label>
        <input id="so-date-filter" type="date" class="bo-filter-input" wire:model.live="filterDate">
    </div>
    <div class="bo-status-pills">
        <button class="bo-pill {{ $filterStatus === '' ? 'active' : '' }}"
            wire:click="$set('filterStatus', '')">All</button>
        <button class="bo-pill p-pending {{ $filterStatus === 'Pending' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Pending')">Pending</button>
        <button class="bo-pill p-partial {{ $filterStatus === 'Partial' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Partial')">Partial</button>
        <button class="bo-pill p-received {{ $filterStatus === 'Complete' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Complete')">Complete</button>
    </div>
</div>

{{-- ─── STATS ───────────────────────────────────────────────────────────────── --}}
<div class="bo-stats">
    <div class="bo-stat">
        <div class="bo-stat-label">Total Listed</div>
        <div class="bo-stat-value accent">{{ $orders->count() }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Revenue</div>
        <div class="bo-stat-value gold">{{ number_format($orders->sum('total_rev'), 0) }}s</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Profit</div>
        @php $totalProfit = $orders->sum('profit'); @endphp
        <div class="bo-stat-value" style="color: {{ $totalProfit >= 0 ? 'var(--green)' : 'var(--red)' }}">
            {{ number_format($totalProfit, 0) }}s
        </div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Pending</div>
        <div class="bo-stat-value">{{ $orders->where('status','Pending')->count() }}</div>
    </div>
</div>

{{-- ─── TABLE (desktop) ────────────────────────────────────────────────────── --}}
<div class="bo-table-desktop">
    <div class="bo-table-wrap">
        <table class="bo-table">
            <thead>
                <tr>
                    <th>Sales ID</th>
                    <th>Item</th>
                    <th>Tier</th>
                    <th>Qty Listed</th>
                    <th>Sold</th>
                    <th>Sell Price</th>
                    <th>Revenue</th>
                    <th>Cost</th>
                    <th>Profit</th>
                    <th>Margin</th>
                    <th>Status</th>
                    <th>Listed</th>
                    <th>Completed In</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                @php
                    $margin = $order->total_rev > 0 ? ($order->profit / $order->total_rev) * 100 : 0;
                    $bclass = match($order->status) {
                        'Pending'  => 'badge-pending',
                        'Partial'  => 'badge-partial',
                        'Complete' => 'badge-received',
                        default    => 'badge-pending',
                    };
                @endphp
                <tr>
                    <td class="mono" style="color:var(--text-3)">{{ $order->sales_id }}</td>
                    <td class="strong">{{ $order->item_name }}</td>
                    <td><span class="bo-tier">{{ $order->tier }}</span></td>
                    <td class="mono">{{ number_format($order->qty) }}</td>
                    <td class="mono">{{ number_format($order->qty_sold) }}</td>
                    <td class="mono" style="color:var(--gold)">{{ number_format($order->sell_price, 2) }}s</td>
                    <td class="mono">{{ number_format($order->total_rev, 0) }}s</td>
                    <td class="mono" style="color:var(--text-3)">{{ number_format($order->total_cost, 0) }}s</td>
                    <td class="mono strong" style="color:{{ $order->profit >= 0 ? 'var(--green)' : 'var(--red)' }}">
                        {{ number_format($order->profit, 0) }}s
                    </td>
                    <td class="mono" style="color:{{ $margin >= 0 ? 'var(--green)' : 'var(--red)' }}">
                        {{ number_format($margin, 1) }}%
                    </td>
                    <td><span class="bo-badge {{ $bclass }}">{{ $order->status }}</span></td>
                    <td class="mono" style="color:var(--text-3)">
                        @if($order->listed_time)
                            {{ \Carbon\Carbon::parse($order->listed_date->format('Y-m-d') . ' ' . $order->listed_time, 'UTC')->setTimezone('Asia/Kuala_Lumpur')->format('h:i A') }}
                            <div style="font-size:10px;opacity:0.5;margin-top:1px">{{ $order->listed_time }} UTC · {{ $order->listed_date->format('d M') }}</div>
                        @else
                            {{ $order->listed_date->format('d M') }}
                        @endif
                    </td>
                    <td>
                        @if($order->status === 'Complete' && $order->completed_at && $order->listed_date && $order->listed_time)
                            @php
                                $soStartedAt = \Carbon\Carbon::parse(
                                    $order->listed_date->format('Y-m-d') . ' ' . $order->listed_time, 'UTC'
                                );
                            @endphp
                            {{ $soStartedAt->diffForHumans($order->completed_at, true) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div class="bo-actions">
                            @if($order->status !== 'Complete')
                                <button class="bo-action-btn"
                                    wire:click="openSellModal({{ $order->id }})">Sell</button>
                            @endif
                            <button class="bo-action-btn bo-action-edit"
                                wire:click="edit({{ $order->id }})">Edit</button>
                            <button class="bo-action-btn bo-action-del"
                                wire:click="delete({{ $order->id }})"
                                wire:confirm="Delete this sales order?">Del</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="14">
                        <div class="bo-empty">
                            <div class="bo-empty-icon">🛒</div>
                            No sales orders found for the selected filters.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ─── CARDS (mobile) ─────────────────────────────────────────────────────── --}}
<div class="bo-cards-mobile">

    @forelse($orders as $order)
    @php
        $soMargin = $order->total_rev > 0 ? ($order->profit / $order->total_rev) * 100 : 0;
        $soBadge  = match($order->status) {
            'Pending'  => 'badge-pending',
            'Partial'  => 'badge-partial',
            'Complete' => 'badge-received',
            default    => 'badge-pending',
        };
    @endphp

    <div class="bo-card">

        {{-- Card header: item name + status badge --}}
        <div class="bo-card-header">
            <div class="bo-card-title-group">
                <div class="bo-card-item-name">{{ $order->item_name }}</div>
                <div class="bo-card-meta-row">
                    <span class="bo-tier">{{ $order->tier }}</span>
                    <span class="bo-card-order-id mono">{{ $order->sales_id }}</span>
                </div>
                <div class="bo-card-meta-row" style="margin-top:4px;font-size:12px;color:var(--text-3,#888)">
                    <span>🕒
                        @if($order->listed_time)
                            {{ \Carbon\Carbon::parse($order->listed_date->format('Y-m-d') . ' ' . $order->listed_time, 'UTC')->setTimezone('Asia/Kuala_Lumpur')->format('H:i') }}
                            <span style="opacity:0.6">({{ $order->listed_time }} UTC)</span>
                        @else
                            —
                        @endif
                    </span>
                    &nbsp;·&nbsp;
                    <span>📅 {{ $order->listed_date->format('d M') }}</span>
                </div>
            </div>
            <span class="bo-badge {{ $soBadge }}">{{ $order->status }}</span>
        </div>

        {{-- Divider --}}
        <div class="bo-card-divider"></div>

        {{-- Stats grid --}}
        <div class="bo-card-grid">

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Qty Listed</div>
                <div class="bo-card-stat-value">{{ number_format($order->qty) }}</div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Sold</div>
                <div class="bo-card-stat-value" style="color:var(--color-received,#4ade80)">{{ number_format($order->qty_sold) }}</div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Sell Price</div>
                <div class="bo-card-stat-value" style="color:var(--gold,#f59e0b)">{{ number_format($order->sell_price, 2) }}s</div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Cost</div>
                <div class="bo-card-stat-value" style="color:var(--text-3,#888)">{{ number_format($order->total_cost, 0) }}s</div>
            </div>

            <div class="bo-card-stat bo-card-stat--wide">
                <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
                    <div>
                        <div class="bo-card-stat-label">Total Revenue</div>
                        <div class="bo-card-stat-value bo-card-stat-value--large">{{ number_format($order->total_rev, 0) }}s</div>
                    </div>
                    <div style="text-align:right">
                        <div class="bo-card-stat-label">Profit</div>
                        <div class="bo-card-stat-value" style="font-size:16px;color:{{ $order->profit >= 0 ? 'var(--green,#4ade80)' : 'var(--red,#f87171)' }}">
                            {{ number_format($order->profit, 0) }}s
                        </div>
                    </div>
                </div>
                <div class="bo-card-fee-badge" style="color:{{ $soMargin >= 0 ? 'var(--green,#4ade80)' : 'var(--red,#f87171)' }};opacity:0.8">
                    Margin: {{ number_format($soMargin, 1) }}%
                </div>
            </div>

            @if($order->status === 'Complete' && $order->completed_at && $order->listed_date && $order->listed_time)
            @php
                $soMobStartedAt = \Carbon\Carbon::parse(
                    $order->listed_date->format('Y-m-d') . ' ' . $order->listed_time, 'UTC'
                );
            @endphp
            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Completed In</div>
                <div class="bo-card-stat-value" style="font-size:12px">{{ $soMobStartedAt->diffForHumans($order->completed_at, true) }}</div>
            </div>
            @endif

        </div>

        {{-- Actions --}}
        <div class="bo-card-actions">
            @if($order->status !== 'Complete')
                <button class="bo-action-btn bo-action-manage"
                    wire:click="openSellModal({{ $order->id }})">
                    💰 Sell
                </button>
            @endif
            <button class="bo-action-btn bo-action-edit"
                wire:click="edit({{ $order->id }})">
                ✏️ Edit
            </button>
            <button class="bo-action-btn bo-action-del"
                wire:click="delete({{ $order->id }})"
                wire:confirm="Delete this sales order?">
                🗑 Delete
            </button>
        </div>

    </div>

    @empty
    <div class="bo-empty">
        <div class="bo-empty-icon">🛒</div>
        No sales orders found.
    </div>
    @endforelse

</div>

{{-- ─── ADD / EDIT MODAL ────────────────────────────────────────────────────── --}}
@if($showModal)
<div class="bo-overlay" role="dialog" aria-modal="true" aria-labelledby="so-modal-title">
    <div class="bo-modal">
        <div class="bo-modal-handle" aria-hidden="true"></div>
        <div class="bo-modal-header">
            <span class="bo-modal-title" id="so-modal-title">
                {{ $editId ? '✏️ Edit Sales Order' : '+ New Sales Order' }}
            </span>
            <button class="bo-modal-close" wire:click="closeModal" aria-label="Close">✕</button>
        </div>
        <div class="bo-modal-body">

            {{-- Buy Order Search: shows on focus, no typing required --}}
            <div class="bo-field">
                <label class="bo-label" for="so-bo-search">Linked Buy Order *</label>
                <input
                    id="so-bo-search"
                    type="text"
                    class="bo-input"
                    wire:model.live="boSearch"
                    wire:focus="openBoDropdown"
                    placeholder="Search by item name or order ID…"
                    autocomplete="off"
                    autocorrect="off"
                    spellcheck="false"
                >
                @if($showBoDropdown)
                <div class="bo-dropdown">
                    @forelse($this->boSuggestions as $bo)
                        @php
                            // Available = qty_received minus ALL ever listed qty (including sold)
                            // This prevents double-listing stock that was already sold
                            $totalListed = $bo->salesOrders()
                                ->where('user_id', Auth::id())
                                ->sum('qty');
                            $avail    = max(0, $bo->qty_received - $totalListed);
                            $landedPu = $bo->qty_ordered > 0 ? $bo->final_landed / $bo->qty_ordered : 0;
                        @endphp
                        <div class="bo-dropdown-item" wire:click="selectBuyOrder({{ $bo->id }})">
                            <span class="strong">{{ $bo->item_name }}</span>
                            <span class="bo-tier" style="margin:0 6px">{{ $bo->tier }}</span>
                            <span style="color:var(--text-3);font-size:0.82rem">
                                {{ $bo->order_id }}
                                &nbsp;·&nbsp; avail: <strong style="color:var(--gold)">{{ number_format($avail) }}</strong>
                                &nbsp;·&nbsp; cost: {{ number_format($landedPu, 2) }}s/ea
                            </span>
                        </div>
                    @empty
                        <div class="bo-dropdown-item" style="color:var(--text-3);cursor:default">
                            No received buy orders with available stock
                        </div>
                    @endforelse
                </div>
                @endif
                @error('buy_order_id') <span class="bo-error" role="alert">{{ $message }}</span> @enderror

                {{-- Hidden inputs: landed cost + available qty — read by JS on every Livewire re-render --}}
                @php
                    $soLandedPuVal = 0;
                    if ($buy_order_id) {
                        $soLinkedBo = \App\Models\BuyOrder::find($buy_order_id);
                        if ($soLinkedBo && $soLinkedBo->qty_ordered > 0) {
                            $soLandedPuVal = $soLinkedBo->final_landed / $soLinkedBo->qty_ordered;
                        }
                    }
                @endphp
                <input type="hidden" id="so-landed-pu-val" value="{{ $soLandedPuVal }}">
            </div>

            {{-- Qty + Sell Price --}}
            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label" for="so-qty">Qty to List *</label>
                    <input id="so-qty" type="number" inputmode="numeric" class="bo-input"
                        wire:model="qty" placeholder="e.g. 5" min="1">
                    @error('qty') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="so-price">Sell Price / ea (silver) *</label>
                    <input id="so-price" type="number" inputmode="decimal" step="0.0001"
                        class="bo-input" wire:model="sell_price" placeholder="0.0000" min="0">
                    @error('sell_price') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- JS-driven live profit preview --}}
            <div class="bo-js-preview" id="so-js-preview">
                <div class="bo-js-preview-header">
                    <span class="bo-js-preview-title">💰 Profit Breakdown</span>
                    <span class="bo-js-preview-badge">4% premium + 2.5% setup</span>
                </div>
                <div class="bo-js-preview-grid so-js-preview-grid">
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Cost / ea</div>
                        <div class="bo-js-stat-val" id="sop-cost-ea">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Premium (4%)</div>
                        <div class="bo-js-stat-val bo-js-orange" id="sop-premium">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Setup (2.5%)</div>
                        <div class="bo-js-stat-val bo-js-orange" id="sop-setup">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Net / ea</div>
                        <div class="bo-js-stat-val" id="sop-net-ea">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Profit / ea</div>
                        <div class="bo-js-stat-val" id="sop-profit-ea">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Margin</div>
                        <div class="bo-js-stat-val" id="sop-margin">—</div>
                    </div>
                    <div class="bo-js-stat bo-js-stat--highlight">
                        <div class="bo-js-stat-label">Total Profit</div>
                        <div class="bo-js-stat-val bo-js-gold" id="sop-total-profit">—</div>
                    </div>
                </div>
                <div class="bo-js-preview-hint" id="sop-hint">
                    Select a buy order &amp; enter sell price to see breakdown
                </div>
            </div>

            {{-- Date + Time --}}
            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label" for="so-date">Listed Date *</label>
                    <input id="so-date" type="date" class="bo-input" wire:model="listed_date">
                    @error('listed_date') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="so-time-local">Listed Time *</label>

                    {{-- User sees + types in LOCAL time --}}
                    <input
                        id="so-time-local"
                        type="time"
                        class="bo-input"
                        placeholder="--:--"
                    >
                    {{-- Hidden: stores UTC, synced to Livewire --}}
                    <input
                        id="so-time"
                        type="hidden"
                        wire:model="listed_time"
                    >
                    <div id="so-time-hint" class="bo-time-hint" aria-live="polite"></div>
                    @error('listed_time') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>
        <div class="bo-modal-footer">
            <button class="bo-btn-cancel" wire:click="closeModal">Cancel</button>
            <button class="bo-btn-primary" wire:click="save">
                {{ $editId ? 'Update Order' : 'List for Sale' }}
            </button>
        </div>
    </div>
</div>
@endif

{{-- ─── MARK SOLD MODAL ─────────────────────────────────────────────────────── --}}
@if($showSellModal)
@php $sellOrder = \App\Models\SalesOrder::find($sellOrderId); @endphp
@if($sellOrder)
<div class="bo-overlay" role="dialog" aria-modal="true">
    <div class="bo-modal">
        <div class="bo-modal-handle" aria-hidden="true"></div>
        <div class="bo-modal-header">
            <span class="bo-modal-title">💰 Record Sale — {{ $sellOrder->item_name }}</span>
            <button class="bo-modal-close" wire:click="$set('showSellModal', false)">✕</button>
        </div>
        <div class="bo-modal-body">
            <div class="bo-preview">
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Listed</div>
                    <div class="bo-preview-value">{{ number_format($sellOrder->qty) }}</div>
                </div>
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Already Sold</div>
                    <div class="bo-preview-value">{{ number_format($sellOrder->qty_sold) }}</div>
                </div>
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Remaining</div>
                    <div class="bo-preview-value gold">{{ number_format($sellOrder->qty - $sellOrder->qty_sold) }}</div>
                </div>
            </div>

            <div class="bo-field">
                <label class="bo-label">Qty Sold Now</label>
                <input type="number" class="bo-input" min="1"
                    max="{{ $sellOrder->qty - $sellOrder->qty_sold }}"
                    wire:model="sell_qty">
                @error('sell_qty') <span class="bo-error">{{ $message }}</span> @enderror
            </div>

            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label">Sold Date *</label>
                    <input type="date" class="bo-input" wire:model="sold_date">
                    @error('sold_date') <span class="bo-error">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="sell-time-local">Sold Time *</label>

                    {{-- User types LOCAL time --}}
                    <input
                        id="sell-time-local"
                        type="time"
                        class="bo-input"
                        placeholder="--:--"
                    >
                    {{-- Hidden UTC stored to Livewire --}}
                    <input
                        id="sell-time"
                        type="hidden"
                        wire:model="sold_time"
                    >
                    <div id="sell-time-hint" class="bo-time-hint" aria-live="polite"></div>
                    @error('sold_time') <span class="bo-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        <div class="bo-modal-footer">
            <button class="bo-btn-cancel" wire:click="$set('showSellModal', false)">Cancel</button>
            <button class="bo-btn-primary" wire:click="confirmSell">Confirm Sale</button>
        </div>
    </div>
</div>
@endif
@endif

</div>{{-- end .bo-root --}}

{{-- ─── STYLES ─────────────────────────────────────────────────────────────── --}}
<style>
    /* ── Mobile card improvements (mirrors buy-orders) ── */
    .bo-card-title-group { flex: 1; min-width: 0; }
    .bo-card-item-name {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.3;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bo-card-meta-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 12px;
    }
    .bo-card-order-id {
        font-size: 11px;
        opacity: 0.5;
    }
    .bo-card-divider {
        height: 1px;
        background: var(--border, rgba(255,255,255,0.08));
        margin: 10px 0;
    }
    .bo-card-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px 8px;
        margin-bottom: 12px;
    }
    .bo-card-stat {
        background: var(--bg-2, rgba(255,255,255,0.04));
        border-radius: 8px;
        padding: 8px 10px;
    }
    .bo-card-stat--wide {
        grid-column: 1 / -1;
        background: var(--bg-2, rgba(255,255,255,0.06));
        border: 1px solid var(--gold-dim, rgba(245,158,11,0.2));
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .bo-card-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.5;
        margin-bottom: 3px;
    }
    .bo-card-stat-value {
        font-size: 14px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .bo-card-stat-value--large {
        font-size: 18px;
        color: var(--gold, #f59e0b);
    }
    .bo-card-fee-badge {
        font-size: 11px;
        margin-top: 3px;
        opacity: 0.7;
        font-variant-numeric: tabular-nums;
    }
    .bo-card-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .bo-card-actions .bo-action-btn {
        flex: 1;
        min-width: 0;
        text-align: center;
        padding: 8px 6px;
        font-size: 13px;
        border-radius: 8px;
    }

    /* ── JS-driven profit preview (6-cell + highlight row) ── */
    .bo-js-preview {
        background: var(--bg-2, rgba(255,255,255,0.04));
        border: 1px solid rgba(245,158,11,0.25);
        border-radius: 10px;
        padding: 12px 14px;
        margin: 4px 0 8px;
    }
    .bo-js-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .bo-js-preview-title {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-1, #ddd);
    }
    .bo-js-preview-badge {
        font-size: 10px;
        padding: 2px 8px;
        background: rgba(245,158,11,0.15);
        color: var(--gold, #f59e0b);
        border-radius: 20px;
        border: 1px solid rgba(245,158,11,0.3);
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .bo-js-preview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 8px;
    }
    /* Sales preview: 6 stats in 3 cols then full-width highlight */
    .so-js-preview-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .bo-js-stat {
        background: var(--bg-3, rgba(255,255,255,0.05));
        border-radius: 7px;
        padding: 8px 10px;
    }
    .bo-js-stat--highlight {
        grid-column: 1 / -1;
        background: rgba(245,158,11,0.08);
        border: 1px solid rgba(245,158,11,0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 14px;
    }
    .bo-js-stat--highlight .bo-js-stat-label { margin-bottom: 0; }
    .bo-js-stat-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.45;
        margin-bottom: 4px;
    }
    .bo-js-stat-val {
        font-size: 13px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--text-1, #eee);
    }
    .bo-js-orange { color: #fb923c; }
    .bo-js-gold   { color: var(--gold, #f59e0b); font-size: 16px; }
    .bo-js-preview-hint {
        font-size: 11px;
        text-align: center;
        opacity: 0.35;
        margin-top: 2px;
    }

    /* ── Time hint below time input ── */
    .bo-time-hint {
        font-size: 11px;
        color: var(--text-3, #777);
        margin-top: 4px;
        min-height: 16px;
        line-height: 1.4;
    }
</style>

{{-- ─── SCRIPTS ────────────────────────────────────────────────────────────── --}}
<script>
(function () {

    /* ── Helpers ── */
    function fmt(n, dec) {
        return n.toLocaleString(undefined, {
            minimumFractionDigits: dec,
            maximumFractionDigits: dec
        });
    }
    function el(id) { return document.getElementById(id); }

    /* ── Landed cost per unit on window so it survives script re-execution.
       The <script> block re-runs on every Livewire re-render of the modal,
       which resets any local 'var'. window._soLandedPU persists across runs. ── */
    if (typeof window._soLandedPU === 'undefined') window._soLandedPU = 0;

    function renderProfitPreview(changedId, changedVal) {
        var qtyEl   = el('so-qty');
        var priceEl = el('so-price');
        var hint    = el('sop-hint');
        if (!qtyEl || !priceEl) return;

        /* Use the live typed value for whichever field triggered the event,
           so we never depend on Livewire having synced the DOM yet */
        var qty   = parseFloat(changedId === 'so-qty'   ? changedVal : qtyEl.value);
        var price = parseFloat(changedId === 'so-price' ? changedVal : priceEl.value);

        /* Only fully blank out when we have nothing useful to show */
        if ((!qty || qty <= 0) && (!price || price <= 0)) {
            ['sop-cost-ea','sop-premium','sop-setup','sop-net-ea',
             'sop-profit-ea','sop-margin','sop-total-profit'].forEach(function(id){
                var e = el(id); if (e) e.textContent = '—';
            });
            ['sop-profit-ea','sop-margin','sop-total-profit'].forEach(function(id){
                var e = el(id); if (e) e.style.color = '';
            });
            if (hint) hint.textContent = 'Select a buy order & enter sell price to see breakdown';
            return;
        }
        /* Use safe fallbacks so partial input still shows useful numbers */
        qty   = qty   > 0 ? qty   : 1;
        price = price > 0 ? price : 0;

        var premium     = price * 0.04;
        var setupFee    = price * 0.025;
        var netPU       = price - premium - setupFee;
        var profitPU    = netPU - window._soLandedPU;
        var totalProfit = profitPU * qty;
        var margin      = price > 0 ? (profitPU / price) * 100 : 0;

        var pos = 'var(--green, #4ade80)';
        var neg = 'var(--red, #f87171)';

        var costEa = el('sop-cost-ea');
        if (costEa) costEa.textContent = window._soLandedPU > 0 ? fmt(window._soLandedPU, 4) + 's' : '—';

        var prem = el('sop-premium');
        if (prem) prem.textContent = fmt(premium, 4) + 's';

        var setup = el('sop-setup');
        if (setup) setup.textContent = fmt(setupFee, 4) + 's';

        var netEa = el('sop-net-ea');
        if (netEa) netEa.textContent = fmt(netPU, 4) + 's';

        var profEa = el('sop-profit-ea');
        if (profEa) {
            profEa.textContent = fmt(profitPU, 4) + 's';
            profEa.style.color = profitPU >= 0 ? pos : neg;
        }

        var marg = el('sop-margin');
        if (marg) {
            marg.textContent = fmt(margin, 1) + '%';
            marg.style.color = margin >= 0 ? pos : neg;
        }

        var totProfit = el('sop-total-profit');
        if (totProfit) {
            totProfit.textContent = fmt(totalProfit, 0) + 's';
            totProfit.style.color = totalProfit >= 0 ? pos : neg;
        }

        if (hint) hint.textContent = qty.toLocaleString() + ' units @ ' + fmt(price, 4) + 's sell · ' +
            (window._soLandedPU > 0 ? fmt(window._soLandedPU, 4) + 's cost' : 'no buy order cost');
    }

    /* ── Time field helper (shared pattern with buy-orders) ── */
    function initTimeField(localId, utcId, hintId) {
        var localInp = el(localId);
        var utcInp   = el(utcId);
        var hint     = el(hintId);
        if (!localInp || !utcInp || !hint) return;
        if (localInp.dataset.boInit === '1') return;
        localInp.dataset.boInit = '1';

        var tzShort = new Date().toLocaleTimeString([], { timeZoneName: 'short' }).split(' ').pop();

        if (utcInp.value) {
            /* EDIT mode: pre-filled UTC → show local */
            var parts   = utcInp.value.split(':');
            var utcDate = new Date(Date.UTC(
                new Date().getUTCFullYear(), new Date().getUTCMonth(), new Date().getUTCDate(),
                parseInt(parts[0], 10), parseInt(parts[1], 10)
            ));
            localInp.value = String(utcDate.getHours()).padStart(2,'0') + ':' + String(utcDate.getMinutes()).padStart(2,'0');
            updateTimeHint(hint, localInp.value, utcInp.value, tzShort);
        } else {
            /* NEW mode: default to current local time */
            var now = new Date();
            localInp.value = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
            syncToUTC(localInp.value, utcInp, hint, tzShort);
            utcInp.dispatchEvent(new Event('input', { bubbles: true }));
        }

        localInp.addEventListener('input', function () {
            syncToUTC(localInp.value, utcInp, hint, tzShort);
            utcInp.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    function syncToUTC(localVal, utcInp, hint, tzShort) {
        if (!localVal) { hint.textContent = ''; utcInp.value = ''; return; }
        var parts = localVal.split(':');
        var now   = new Date();
        var local = new Date(
            now.getFullYear(), now.getMonth(), now.getDate(),
            parseInt(parts[0], 10), parseInt(parts[1], 10)
        );
        var uhh    = String(local.getUTCHours()).padStart(2, '0');
        var umm    = String(local.getUTCMinutes()).padStart(2, '0');
        var utcVal = uhh + ':' + umm;
        utcInp.value = utcVal;
        updateTimeHint(hint, localVal, utcVal, tzShort);
    }

    function updateTimeHint(hint, localVal, utcVal, tzShort) {
        var localDisplay = fmtTime12(localVal);
        hint.innerHTML =
            '<span style="color:var(--gold,#f59e0b)">&#128336; ' + localDisplay + '</span>' +
            ' <span style="opacity:0.6">(' + tzShort + ')</span>' +
            ' &nbsp;·&nbsp; <strong>' + utcVal + ' UTC</strong>';
    }

    function fmtTime12(val) {
        var parts = val.split(':');
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    /* ── Read landed cost from the Blade-rendered hidden input.
       This is the most reliable approach — no custom events needed.
       The hidden input value is always in sync with Livewire's buy_order_id.
       Also keep the window event as a fallback if the PHP dispatch is wired up. ── */
    function syncLandedPU() {
        var inp = el('so-landed-pu-val');
        if (inp) {
            var v = parseFloat(inp.value);
            if (!isNaN(v)) window._soLandedPU = v;
        }
    }
    window.addEventListener('so-landed-pu', function (e) {
        window._soLandedPU = parseFloat(e.detail.landedPu !== undefined ? e.detail.landedPu : (e.detail[0] || 0));
        renderProfitPreview();
    });

    /* ── Input listeners: fire on every keystroke and spinner click.
       We pass the live value from the event directly so Livewire's DOM
       diffing cannot interfere — renderProfitPreview reads the elements
       but we also accept an override map for the field being typed into. ── */
    function soInputHandler(e) {
        if (!e.target) return;
        var id = e.target.id;
        if (id === 'so-qty' || id === 'so-price') {
            /* Pass the raw typed value directly to bypass any DOM timing issue */
            renderProfitPreview(id, e.target.value);
        }
    }
    document.addEventListener('input',  soInputHandler);
    document.addEventListener('change', soInputHandler);

    /* ── Re-run after every Livewire DOM patch ── */
    function afterLivewire() {
        /* Reset init flags so freshly rendered modals always initialise */
        ['so-time-local','sell-time-local'].forEach(function(id){
            var inp = el(id); if (inp) inp.dataset.boInit = '';
        });
        setTimeout(function () {
            initTimeField('so-time-local',   'so-time',   'so-time-hint');
            initTimeField('sell-time-local', 'sell-time', 'sell-time-hint');
            syncLandedPU();
            renderProfitPreview();
        }, 80);
    }
    document.addEventListener('livewire:updated', afterLivewire);
    document.addEventListener('livewire:update',  afterLivewire); /* Livewire v2 */
    document.addEventListener('DOMContentLoaded', afterLivewire);

})();
</script>