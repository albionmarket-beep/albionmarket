{{-- resources/views/livewire/buy-orders.blade.php --}}
{{--
    Styles live in: public/css/buy-orders.css
    Include in your layout: <link rel="stylesheet" href="{{ asset('css/buy-orders.css') }}">
    Or in a @push('styles') stack if your layout supports it.
--}}

<div class="bo-root">

{{-- ─── TOP BAR ─────────────────────────────────────────────────────────────── --}}
<div class="bo-topbar">
    <h1 class="bo-title">Buy <span>Orders</span></h1>
    <button class="bo-btn-primary" wire:click="openModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M12 5v14M5 12h14"/>
        </svg>
        Add Buy Order
    </button>
</div>

{{-- ─── FILTERS ─────────────────────────────────────────────────────────────── --}}
<div class="bo-filters">
    <div class="bo-filter-group">
        <label for="bo-date-filter">Date</label>
        <input
            id="bo-date-filter"
            type="date"
            class="bo-filter-input"
            wire:model.live="filterDate"
        >
    </div>

    <div class="bo-status-pills">
        <button class="bo-pill {{ $filterStatus === '' ? 'active' : '' }}"
            wire:click="$set('filterStatus', '')">All</button>
        <button class="bo-pill p-pending {{ $filterStatus === 'Pending' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Pending')">Pending</button>
        <button class="bo-pill p-partial {{ $filterStatus === 'Partial' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Partial')">Partial</button>
        <button class="bo-pill p-received {{ $filterStatus === 'Received' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Received')">Received</button>
        <button class="bo-pill p-soldout {{ $filterStatus === 'Sold Out' ? 'active' : '' }}"
            wire:click="$set('filterStatus', 'Sold Out')">Sold Out</button>
    </div>
</div>

{{-- ─── STATS ───────────────────────────────────────────────────────────────── --}}
<div class="bo-stats">
    <div class="bo-stat">
        <div class="bo-stat-label">Total Orders</div>
        <div class="bo-stat-value accent">{{ $orders->count() }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Qty</div>
        <div class="bo-stat-value">{{ number_format($orders->sum('qty_ordered')) }}</div>
    </div>
    <div class="bo-stat">
        <div class="bo-stat-label">Total Landed</div>
        <div class="bo-stat-value gold">{{ number_format($orders->sum('final_landed'), 0) }}s</div>
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
                    <th>Order ID</th>
                    <th>Item</th>
                    <th>Tier / Ench</th>
                    <th>Qty</th>
                    <th>Cost / ea</th>
                    <th>Landed</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th>Pending</th>
                    <th>Completed In</th>
                    <th>City</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="mono" style="color:var(--text-3)">{{ $order->order_id }}</td>
                    <td class="strong">{{ $order->item_name }}</td>
                    <td>
                        <span class="bo-tier">{{ $order->tier }}</span>
                        @if($order->enchantment)
                            <span class="bo-ench" style="margin-left:4px">{{ $order->enchantment }}</span>
                        @endif
                    </td>
                    <td class="mono">{{ number_format($order->qty_ordered) }}</td>
                    <td class="mono" style="color:var(--gold)">{{ number_format($order->cost_per, 2) }}s</td>
                    <td class="mono strong">
                        {{ number_format($order->final_landed, 0) }}s
                        @php $setupFee = $order->cost_per * 0.025 * $order->qty_ordered; @endphp
                        <div style="font-size:11px;color:var(--text-3,#888);font-weight:400;margin-top:2px">({{ number_format($setupFee, 2) }}s fee)</div>
                    </td>
                    <td class="mono" style="color:var(--text-3)">
                        @if($order->ordered_time)
                            {{ \Carbon\Carbon::parse($order->ordered_time, 'UTC')->setTimezone('Asia/Kuala_Lumpur')->format('h:i A') }}
                            <div style="font-size:10px;opacity:0.5;margin-top:1px">{{ $order->ordered_time }} UTC</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @php
                            $bclass = match($order->status) {
                                'Pending'  => 'badge-pending',
                                'Partial'  => 'badge-partial',
                                'Received' => 'badge-received',
                                'Sold Out' => 'badge-soldout',
                                default    => 'badge-pending',
                            };
                        @endphp
                        <span class="bo-badge {{ $bclass }}">{{ $order->status }}</span>
                    </td>
                    <td class="mono">{{ number_format($order->qty_received) }}</td>
                    <td class="mono">{{ number_format($order->qty_ordered - $order->qty_received) }}</td>
                    <td>
                        @if($order->completed_at && $order->ordered_date && $order->ordered_time)
                            @php
                                $startedAt = \Carbon\Carbon::parse(
                                    $order->ordered_date->format('Y-m-d') . ' ' . $order->ordered_time
                                );
                            @endphp
                            {{ $startedAt->diffForHumans($order->completed_at, true) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $order->city ?? '—' }}</td>
                    <td>
                        <div class="bo-actions">
                            @if($order->qty_received < $order->qty_ordered)
                                <button class="bo-action-btn"
                                    wire:click="manage({{ $order->id }})">
                                    Manage
                                </button>
                            @endif
                            <button class="bo-action-btn bo-action-edit"
                                wire:click="edit({{ $order->id }})">Edit</button>
                            <button class="bo-action-btn bo-action-del"
                                wire:click="delete({{ $order->id }})"
                                wire:confirm="Delete this order?">Del</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13">
                        <div class="bo-empty">
                            <div class="bo-empty-icon">📦</div>
                            No buy orders found for the selected filters.
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
        $mobileSetupFee = $order->cost_per * 0.025 * $order->qty_ordered;
        $mobileBadge = match($order->status) {
            'Pending'  => 'badge-pending',
            'Partial'  => 'badge-partial',
            'Received' => 'badge-received',
            'Sold Out' => 'badge-soldout',
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
                    @if($order->enchantment)
                        <span class="bo-ench">{{ $order->enchantment }}</span>
                    @endif
                    <span class="bo-card-order-id mono">{{ $order->order_id }}</span>
                </div>
                <div class="bo-card-meta-row" style="margin-top:4px;font-size:12px;color:var(--text-3,#888)">
                    <span>🕒
                        {{
                            \Carbon\Carbon::parse($order->ordered_time, 'UTC')
                                ->setTimezone('Asia/Kuala_Lumpur')
                                ->format('H:i')
                        }}
                        <span style="opacity:0.6">({{ $order->ordered_time }} UTC)</span>
                    </span>
                    &nbsp;·&nbsp;
                    <span>📍 {{ $order->city ?? '—' }}</span>
                </div>
            </div>
            <span class="bo-badge {{ $mobileBadge }}">{{ $order->status }}</span>
        </div>

        {{-- Divider --}}
        <div class="bo-card-divider"></div>

        {{-- Stats grid --}}
        <div class="bo-card-grid">

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Qty Ordered</div>
                <div class="bo-card-stat-value">{{ number_format($order->qty_ordered) }}</div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Received</div>
                <div class="bo-card-stat-value" style="color:var(--color-received,#4ade80)">{{ number_format($order->qty_received) }}</div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Pending</div>
                <div class="bo-card-stat-value" style="color:var(--gold,#f59e0b)">
                    {{ number_format($order->qty_ordered - $order->qty_received) }}
                </div>
            </div>

            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Cost / ea</div>
                <div class="bo-card-stat-value" style="color:var(--gold,#f59e0b)">{{ number_format($order->cost_per, 2) }}s</div>
            </div>

            <div class="bo-card-stat bo-card-stat--wide">
                <div class="bo-card-stat-label">Total Landed</div>
                <div class="bo-card-stat-value bo-card-stat-value--large">{{ number_format($order->final_landed, 0) }}s</div>
                <div class="bo-card-fee-badge">({{ number_format($mobileSetupFee, 2) }}s setup fee · 2.5%)</div>
            </div>

            @if($order->completed_at && $order->ordered_date && $order->ordered_time)
            @php
                $mobStartedAt = \Carbon\Carbon::parse(
                    $order->ordered_date->format('Y-m-d') . ' ' . $order->ordered_time
                );
            @endphp
            <div class="bo-card-stat">
                <div class="bo-card-stat-label">Completed In</div>
                <div class="bo-card-stat-value" style="font-size:12px">{{ $mobStartedAt->diffForHumans($order->completed_at, true) }}</div>
            </div>
            @endif

        </div>

        {{-- Actions --}}
        <div class="bo-card-actions">
            @if($order->qty_received < $order->qty_ordered)
                <button class="bo-action-btn bo-action-manage"
                    wire:click="manage({{ $order->id }})">
                    📥 Manage
                </button>
            @endif
            <button class="bo-action-btn bo-action-edit"
                wire:click="edit({{ $order->id }})">
                ✏️ Edit
            </button>
            <button class="bo-action-btn bo-action-del"
                wire:click="delete({{ $order->id }})"
                wire:confirm="Delete this order?">
                🗑 Delete
            </button>
        </div>

    </div>

    @empty
    <div class="bo-empty">
        <div class="bo-empty-icon">📦</div>
        No buy orders found.
    </div>
    @endforelse

</div>


{{-- ─── ADD / EDIT MODAL ───────────────────────────────────────────────────── --}}
@if($showModal)
<div class="bo-overlay" role="dialog" aria-modal="true" aria-labelledby="bo-modal-title">
    <div class="bo-modal">

        <div class="bo-modal-handle" aria-hidden="true"></div>

        <div class="bo-modal-header">
            <span class="bo-modal-title" id="bo-modal-title">
                {{ $editId ? '✏️ Edit Order' : '+ New Buy Order' }}
            </span>
            <button class="bo-modal-close" wire:click="closeModal" aria-label="Close modal">✕</button>
        </div>

        <div class="bo-modal-body">

            {{-- Item Search --}}
            <div class="bo-field">
                <label class="bo-label" for="bo-item-search">Item Name *</label>
                <input
                    id="bo-item-search"
                    type="text"
                    class="bo-input"
                    wire:model.live="itemSearch"
                    placeholder="Search or type new item…"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                >
                @if($showItemDropdown && strlen($itemSearch) > 0)
                <div class="bo-dropdown">
                    @foreach($this->itemSuggestions as $suggestion)
                        <div class="bo-dropdown-item"
                             wire:click="selectItem({{ $suggestion->id }}, '{{ addslashes($suggestion->name) }}')">
                            {{ $suggestion->name }}
                        </div>
                    @endforeach
                    @if($this->itemSuggestions->isEmpty() || !$this->itemSuggestions->contains('name', $itemSearch))
                        <div class="bo-dropdown-item bo-dropdown-new"
                             wire:click="selectItem(null, '{{ addslashes($itemSearch) }}')">
                            "{{ $itemSearch }}" — add as new item
                        </div>
                    @endif
                </div>
                @endif
                @error('item_name') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
            </div>

            {{-- City --}}
            <div class="bo-field">
                <label class="bo-label" for="bo-city">City</label>
                <input
                    id="bo-city"
                    type="text"
                    class="bo-input"
                    wire:model.live="citySearch"
                    placeholder="Search city…"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    wire:focus="$set('showCityDropdown', true)"
                >
                @if($showCityDropdown)
                <div class="bo-dropdown">
                    @foreach($this->citySuggestions as $city)
                        <div class="bo-dropdown-item"
                            wire:click="selectCity('{{ addslashes($city) }}')">
                            {{ $city }}
                        </div>
                    @endforeach
                    @if($this->citySuggestions->isEmpty())
                        <div class="bo-dropdown-item" style="opacity:0.5;pointer-events:none">
                            No cities found
                        </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Tier + Enchantment --}}
            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label" for="bo-tier">Tier *</label>
                    <select id="bo-tier" class="bo-input" wire:model="tier">
                        <option value="">Select tier…</option>
                        @foreach(['T1','T2','T3','T4','T5','T6','T7','T8'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('tier') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="bo-enchantment">Enchantment</label>
                    <select id="bo-enchantment" class="bo-input" wire:model="enchantment">
                        @foreach(range(0, 4) as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Qty + Cost --}}
            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label" for="bo-qty">Qty Ordered *</label>
                    <input
                        id="bo-qty"
                        type="number"
                        inputmode="numeric"
                        class="bo-input"
                        wire:model="qty_ordered"
                        placeholder="e.g. 10"
                        min="1"
                    >
                    @error('qty_ordered') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="bo-cost">Cost / ea (silver) *</label>
                    <input
                        id="bo-cost"
                        type="number"
                        inputmode="decimal"
                        step="0.0001"
                        class="bo-input"
                        wire:model="cost_per"
                        placeholder="0.0000"
                        min="0"
                    >
                    @error('cost_per') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Live cost preview: fully JS-driven, always rendered, updates on every keystroke --}}
            <div class="bo-js-preview" id="bo-js-preview">
                <div class="bo-js-preview-header">
                    <span class="bo-js-preview-title">💰 Cost Breakdown</span>
                    <span class="bo-js-preview-badge">2.5% market fee</span>
                </div>
                <div class="bo-js-preview-grid">
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Base Cost</div>
                        <div class="bo-js-stat-val" id="bop-base">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Fee / ea</div>
                        <div class="bo-js-stat-val bo-js-orange" id="bop-fee-ea">—</div>
                    </div>
                    <div class="bo-js-stat">
                        <div class="bo-js-stat-label">Total Fee</div>
                        <div class="bo-js-stat-val bo-js-orange" id="bop-fee-total">—</div>
                    </div>
                    <div class="bo-js-stat bo-js-stat--highlight">
                        <div class="bo-js-stat-label">Total Landed</div>
                        <div class="bo-js-stat-val bo-js-gold" id="bop-landed">—</div>
                    </div>
                </div>
                <div class="bo-js-preview-hint" id="bop-hint">Enter qty &amp; cost to see breakdown</div>
            </div>

            {{-- Date + Time --}}
            <div class="bo-row2">
                <div class="bo-field">
                    <label class="bo-label" for="bo-date">Ordered Date *</label>
                    <input
                        id="bo-date"
                        type="date"
                        class="bo-input"
                        wire:model="ordered_date"
                    >
                    @error('ordered_date') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
                <div class="bo-field">
                    <label class="bo-label" for="bo-time-local">Ordered Time *</label>

                    {{-- User sees + types in LOCAL time --}}
                    <input
                        id="bo-time-local"
                        type="time"
                        class="bo-input"
                        placeholder="--:--"
                    >
                    {{-- Hidden: stores UTC, synced to Livewire --}}
                    <input
                        id="bo-time"
                        type="hidden"
                        wire:model="ordered_time"
                    >

                    {{-- Shows: "08:45 AM (MYT)  ·  00:45 UTC" --}}
                    <div id="bo-time-hint" class="bo-time-hint" aria-live="polite"></div>
                    @error('ordered_time') <span class="bo-error" role="alert">{{ $message }}</span> @enderror
                </div>
            </div>

        </div>

        <div class="bo-modal-footer">
            <button class="bo-btn-cancel" wire:click="closeModal">Cancel</button>
            <button class="bo-btn-primary" wire:click="save">
                {{ $editId ? 'Update Order' : 'Save Order' }}
            </button>
        </div>

    </div>
</div>
@endif

{{-- ─── MANAGE MODAL ───────────────────────────────────────────────────────── --}}
@if($showManageModal)
<div class="bo-overlay">
    <div class="bo-modal">
        <div class="bo-modal-header">
            <span class="bo-modal-title">Manage Order</span>
            <button class="bo-modal-close" wire:click="$set('showManageModal', false)">✕</button>
        </div>

        @php $manageOrder = \App\Models\BuyOrder::find($manageId); @endphp

        @if($manageOrder)
        <div class="bo-modal-body">
            <div class="bo-preview">
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Ordered</div>
                    <div class="bo-preview-value">{{ $manageOrder->qty_ordered }}</div>
                </div>
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Received</div>
                    <div class="bo-preview-value">{{ $manageOrder->qty_received }}</div>
                </div>
                <div class="bo-preview-item">
                    <div class="bo-preview-label">Pending</div>
                    <div class="bo-preview-value gold">
                        {{ $manageOrder->qty_ordered - $manageOrder->qty_received }}
                    </div>
                </div>
            </div>

            <div class="bo-field">
                <label class="bo-label">Qty Receiving Now</label>
                <input
                    type="number"
                    class="bo-input"
                    min="1"
                    max="{{ $manageOrder->qty_ordered - $manageOrder->qty_received }}"
                    wire:model="receive_qty"
                >
                @error('receive_qty')
                    <span class="bo-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="bo-modal-footer">
            <button class="bo-btn-cancel" wire:click="$set('showManageModal', false)">Cancel</button>
            <button class="bo-btn-primary" wire:click="receiveStock">Confirm Receive</button>
        </div>
        @endif
    </div>
</div>
@endif

</div>{{-- end .bo-root --}}

{{-- ─── SCRIPTS ────────────────────────────────────────────────────────────── --}}
<style>
    /* ── Mobile card improvements ── */
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
        opacity: 0.55;
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

    /* ── JS-driven live cost preview ── */
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

    /* ── Fee preview: pure JS, fires on every keystroke ── */
    function renderFeePreview() {
        var qtyEl  = el('bo-qty');
        var costEl = el('bo-cost');
        var hint   = el('bop-hint');
        if (!qtyEl || !costEl) return;

        var qty  = parseFloat(qtyEl.value);
        var cost = parseFloat(costEl.value);

        if (!qty || !cost || qty <= 0 || cost <= 0) {
            ['bop-base','bop-fee-ea','bop-fee-total','bop-landed'].forEach(function(id){
                var e = el(id); if (e) e.textContent = '—';
            });
            if (hint) hint.textContent = 'Enter qty & cost to see breakdown';
            return;
        }

        var feePerEa   = cost * 0.025;
        var feeTotal   = feePerEa * qty;
        var baseCost   = cost * qty;
        var landed     = (cost + feePerEa) * qty;

        var b = el('bop-base');      if (b) b.textContent = fmt(baseCost, 2) + 's';
        var f = el('bop-fee-ea');    if (f) f.textContent = fmt(feePerEa, 4) + 's';
        var t = el('bop-fee-total'); if (t) t.textContent = fmt(feeTotal, 2) + 's';
        var l = el('bop-landed');    if (l) l.textContent = fmt(landed, 2) + 's';
        if (hint) hint.textContent = qty.toLocaleString() + ' units @ ' + fmt(cost, 4) + 's each';
    }

    /* ── Time field: user types LOCAL time, UTC is stored in hidden input ── */
    function initTimeField() {
        var localInp  = el('bo-time-local');
        var utcInp    = el('bo-time');
        var hint      = el('bo-time-hint');
        if (!localInp || !utcInp || !hint) return;

        /* Avoid re-initialising if the user already touched the field */
        if (localInp.dataset.boInit === '1') return;
        localInp.dataset.boInit = '1';

        var tz      = Intl.DateTimeFormat().resolvedOptions().timeZone;
        var tzShort = new Date().toLocaleTimeString([], { timeZoneName: 'short' }).split(' ').pop();

        if (utcInp.value) {
            /* EDIT mode: Livewire pre-filled UTC — convert back to local for display */
            var parts   = utcInp.value.split(':');
            var utcDate = new Date(Date.UTC(
                new Date().getUTCFullYear(), new Date().getUTCMonth(), new Date().getUTCDate(),
                parseInt(parts[0], 10), parseInt(parts[1], 10)
            ));
            localInp.value = String(utcDate.getHours()).padStart(2,'0') + ':' + String(utcDate.getMinutes()).padStart(2,'0');
            updateHint(hint, localInp.value, utcInp.value, tz, tzShort);
        } else {
            /* NEW mode: default to current local time */
            var now = new Date();
            localInp.value = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
            syncToUTC(localInp.value, utcInp, hint, tz, tzShort);
            /* Notify Livewire of the pre-filled UTC value */
            utcInp.dispatchEvent(new Event('input', { bubbles: true }));
        }

        localInp.addEventListener('input', function () {
            syncToUTC(localInp.value, utcInp, hint, tz, tzShort);
            utcInp.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    function syncToUTC(localVal, utcInp, hint, tz, tzShort) {
        if (!localVal) { hint.textContent = ''; utcInp.value = ''; return; }
        var parts   = localVal.split(':');
        var now     = new Date();
        /* Build a Date in local time then read back its UTC hours/mins */
        var local   = new Date(
            now.getFullYear(), now.getMonth(), now.getDate(),
            parseInt(parts[0], 10), parseInt(parts[1], 10)
        );
        var uhh = String(local.getUTCHours()).padStart(2, '0');
        var umm = String(local.getUTCMinutes()).padStart(2, '0');
        var utcVal  = uhh + ':' + umm;
        utcInp.value = utcVal;
        updateHint(hint, localVal, utcVal, tz, tzShort);
    }

    function updateHint(hint, localVal, utcVal, tz, tzShort) {
        var localDisplay = fmtTime12(localVal);
        hint.innerHTML =
            '<span style="color:var(--gold,#f59e0b)">&#128336; ' + localDisplay + '</span>' +
            ' <span style="opacity:0.6">(' + tzShort + ')</span>' +
            ' &nbsp;·&nbsp; <strong>' + utcVal + ' UTC</strong>';
    }

    function fmtTime12(val) {
        /* Convert "HH:MM" → "8:45 AM" */
        var parts = val.split(':');
        var h = parseInt(parts[0], 10);
        var m = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    /* ── Event listeners ── */
    document.addEventListener('input', function (e) {
        if (!e.target) return;
        var id = e.target.id;
        if (id === 'bo-qty' || id === 'bo-cost') renderFeePreview();
    });

    /* Re-run after every Livewire DOM patch (modal open/close re-renders) */
    function afterLivewire() {
        /* Clear init flag first so a freshly-rendered modal always gets initialised */
        var prev = el('bo-time-local');
        if (prev) prev.dataset.boInit = '';
        setTimeout(function () {
            renderFeePreview();
            initTimeField();
        }, 40);
    }
    document.addEventListener('livewire:updated', afterLivewire);
    document.addEventListener('livewire:update',  afterLivewire); /* Livewire v2 */
    document.addEventListener('DOMContentLoaded', afterLivewire);

})();
</script>