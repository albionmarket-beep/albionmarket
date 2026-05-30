<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SalesOrder;
use App\Models\BuyOrder;
use Illuminate\Support\Facades\Auth;

class SalesOrders extends Component
{
    // ── Form fields ──────────────────────────────────────────────────────────
    public $buy_order_id  = null;
    public $sell_price;
    public $qty;
    public $listed_date;
    public $listed_time;
    public $editId        = null;

    // ── Mark-sold fields ─────────────────────────────────────────────────────
    public $sell_qty;
    public $sold_date;
    public $sold_time;

    // ── Modal state ──────────────────────────────────────────────────────────
    public $showModal     = false;
    public $showSellModal = false;
    public $sellOrderId   = null;

    // ── Buy-order search ─────────────────────────────────────────────────────
    public $boSearch         = '';
    public $showBoDropdown   = false;
    public $selectedBoLabel  = '';

    // ── Filters ──────────────────────────────────────────────────────────────
    public $filterDate;
    public $filterStatus = '';

    // ── Lifecycle ────────────────────────────────────────────────────────────
    public function mount()
    {
        $this->filterDate  = now()->format('Y-m-d');
        $this->listed_date = now()->format('Y-m-d');
        $this->listed_time = now()->format('H:i');
        $this->sold_date   = now()->format('Y-m-d');
        $this->sold_time   = now()->format('H:i');
    }

    // ── Available stock helper ────────────────────────────────────────────────
    // True available = qty_received − SUM(qty) of ALL sales orders ever created
    // for this buy order (excluding the current edit).
    // We use SUM(qty) — not SUM(qty - qty_sold) — so completed/sold orders
    // still consume from the pool and cannot be double-listed.
    private function calcAvail(BuyOrder $bo, ?int $excludeSalesOrderId = null): int
    {
        $totalListed = SalesOrder::where('buy_order_id', $bo->id)
            ->where('user_id', Auth::id())
            ->when($excludeSalesOrderId, fn($q) => $q->where('id', '!=', $excludeSalesOrderId))
            ->sum('qty');

        return max(0, $bo->qty_received - (int) $totalListed);
    }

    // ── Buy-order suggestions ─────────────────────────────────────────────────
    public function getBoSuggestionsProperty()
    {
        $query = BuyOrder::where('user_id', Auth::id())
            ->whereIn('status', ['Received', 'Partial'])
            // Only show buy orders that still have stock available
            // (qty_received > total ever listed across all sales orders)
            ->whereRaw('qty_received > (
                SELECT COALESCE(SUM(so2.qty), 0)
                FROM sales_orders so2
                WHERE so2.buy_order_id = buy_orders.id
                  AND so2.user_id = ?
            )', [Auth::id()]);

        if (strlen($this->boSearch) > 0) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->boSearch . '%')
                  ->orWhere('order_id', 'like', '%' . $this->boSearch . '%');
            });
        }

        return $query->orderByDesc('ordered_date')->limit(10)->get();
    }

    public function updatedBoSearch()
    {
        $this->showBoDropdown  = true;
        $this->buy_order_id    = null;
        $this->selectedBoLabel = '';
    }

    public function openBoDropdown()
    {
        $this->showBoDropdown = true;
    }

    public function selectBuyOrder($id)
    {
        $bo = BuyOrder::where('user_id', Auth::id())->findOrFail($id);

        $avail = $this->calcAvail($bo, $this->editId);

        $landedPerUnit = $bo->qty_ordered > 0
            ? round($bo->final_landed / $bo->qty_ordered, 4)
            : 0;

        $this->buy_order_id    = $bo->id;
        $this->selectedBoLabel = "{$bo->item_name} [{$bo->tier}] — {$bo->order_id} (avail: {$avail})";
        $this->boSearch        = $this->selectedBoLabel;
        $this->showBoDropdown  = false;

        // Pre-fill qty with true available stock
        $this->qty = $avail;

        // Dispatch landed cost to JS so the profit preview can show Cost/EA correctly
        $this->dispatch('so-landed-pu', landedPu: $landedPerUnit);
    }

    // ── Save / Update ────────────────────────────────────────────────────────
    public function save()
    {
        $this->validate([
            'buy_order_id' => 'required|integer',
            'sell_price'   => 'required|numeric|min:0.0001',
            'qty'          => 'required|integer|min:1',
            'listed_date'  => 'required|date',
            'listed_time'  => 'required',
        ]);

        $bo    = BuyOrder::where('user_id', Auth::id())->findOrFail($this->buy_order_id);
        $avail = $this->calcAvail($bo, $this->editId);

        // Prevent listing more than what's actually available
        if ($this->qty > $avail) {
            $this->addError('qty', "Only {$avail} units available from this buy order.");
            return;
        }

        $this->validate([
            'qty' => "required|integer|min:1|max:{$avail}",
        ]);

        $landedPerUnit = $bo->qty_ordered > 0 ? $bo->final_landed / $bo->qty_ordered : 0;
        $premiumTax    = $this->sell_price * 0.04;
        $setupFee      = $this->sell_price * 0.025;

        $data = [
            'user_id'      => Auth::id(),
            'buy_order_id' => $this->buy_order_id,
            'item_name'    => $bo->item_name,
            'tier'         => $bo->tier,
            'qty'          => $this->qty,
            'sell_price'   => $this->sell_price,
            'premium_tax'  => $premiumTax,
            'setup_fee'    => $setupFee,
            'avg_cost'     => $landedPerUnit,
            'listed_date'  => $this->listed_date,
            'listed_time'  => $this->listed_time,
        ];

        if ($this->editId) {
            $so = SalesOrder::where('user_id', Auth::id())->findOrFail($this->editId);

            // Un-sync the old reserved qty, then re-sync the new qty
            $bo->decrement('qty_synced', $so->qty - $so->qty_sold);
            $so->update($data);
            $bo->increment('qty_synced', $this->qty - $so->fresh()->qty_sold);
        } else {
            $data['sales_id'] = 'SO-' . now()->format('YmdHis') . '-' . str_pad(
                SalesOrder::where('user_id', Auth::id())
                    ->whereDate('listed_date', $this->listed_date)
                    ->count() + 1,
                4, '0', STR_PAD_LEFT
            );
            $data['status']   = 'Pending';
            $data['qty_sold'] = 0;

            SalesOrder::create($data);

            // Reserve the full listed qty in the buy order
            $bo->increment('qty_synced', $this->qty);
        }

        $this->closeModal();
    }

    // ── Edit ─────────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $so = SalesOrder::where('user_id', Auth::id())->findOrFail($id);
        $bo = $so->buyOrder;

        $avail = $this->calcAvail($bo, $so->id);

        $this->editId          = $so->id;
        $this->buy_order_id    = $so->buy_order_id;
        $this->selectedBoLabel = "{$so->item_name} [{$so->tier}] — {$bo->order_id} (avail: {$avail})";
        $this->boSearch        = $this->selectedBoLabel;
        $this->sell_price      = $so->sell_price;
        $this->qty             = $so->qty;
        $this->listed_date     = $so->listed_date->format('Y-m-d');
        $this->listed_time     = $so->listed_time;
        $this->showModal       = true;
    }

    // ── Mark Sold ─────────────────────────────────────────────────────────────
    public function openSellModal($id)
    {
        $so = SalesOrder::where('user_id', Auth::id())->findOrFail($id);
        $this->sellOrderId   = $id;
        $this->sell_qty      = $so->qty - $so->qty_sold;
        $this->sold_date     = now()->format('Y-m-d');
        $this->sold_time     = now()->format('H:i');
        $this->showSellModal = true;
    }

    public function confirmSell()
    {
        $so        = SalesOrder::where('user_id', Auth::id())->findOrFail($this->sellOrderId);
        $remaining = $so->qty - $so->qty_sold;

        $this->validate([
            'sell_qty'  => "required|integer|min:1|max:{$remaining}",
            'sold_date' => 'required|date',
            'sold_time' => 'required',
        ]);

        $newQtySold = $so->qty_sold + $this->sell_qty;

        $premiumTax = $so->sell_price * 0.04;
        $setupFee   = $so->sell_price * 0.025;
        $netPerUnit = $so->sell_price - $premiumTax - $setupFee;

        $so->qty_sold   = $newQtySold;
        $so->total_rev  = $so->sell_price * $newQtySold;
        $so->total_cost = $so->avg_cost * $newQtySold;
        $so->profit     = ($netPerUnit - $so->avg_cost) * $newQtySold;
        $so->sold_date  = $this->sold_date;
        $so->sold_time  = $this->sold_time;

        if ($newQtySold >= $so->qty) {
            $so->status       = 'Complete';
            $so->completed_at = now();
        } elseif ($newQtySold > 0) {
            $so->status = 'Partial';
        }

        $so->save();

        // Release sold qty from buy order's reserved stock
        $so->buyOrder->decrement('qty_synced', $this->sell_qty);

        $this->showSellModal = false;
        session()->flash('success', 'Sale recorded.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    public function delete($id)
    {
        $so = SalesOrder::where('user_id', Auth::id())->findOrFail($id);

        // Only release the unsold reserved qty back to the buy order
        $unsold = $so->qty - $so->qty_sold;
        if ($unsold > 0) {
            $so->buyOrder?->decrement('qty_synced', $unsold);
        }

        $so->delete();
    }

    // ── Modal helpers ─────────────────────────────────────────────────────────
    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'buy_order_id', 'boSearch', 'selectedBoLabel', 'showBoDropdown',
            'sell_price', 'qty', 'editId',
        ]);
        $this->listed_date = now()->format('Y-m-d');
        $this->listed_time = now()->format('H:i');
    }

    // ── Render ────────────────────────────────────────────────────────────────
    public function render()
    {
        $query = SalesOrder::where('user_id', Auth::id())
            ->with('buyOrder');

        if ($this->filterDate) {
            $query->whereDate('listed_date', $this->filterDate);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $orders = $query->latest('listed_date')->get();

        return view('livewire.sales-orders', ['orders' => $orders]);
    }
}