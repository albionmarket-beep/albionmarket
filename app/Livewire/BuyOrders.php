<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BuyOrder;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;

class BuyOrders extends Component
{
    // ── Form fields ──────────────────────────────────────────────────────────
    public $item_name      = '';
    public $item_id        = null;
    public $tier           = '';
    public $enchantment    = '0';
    public $city            = '';
    public $citySearch      = '';
    public $showCityDropdown = false;
    public $qty_ordered;
    public $cost_per;
    public $ordered_date;
    public $ordered_time;
    public $editId         = null;

    // ── Manage modal ─────────────────────────────────────────────────────────
    public $manageId       = null;
    public $receive_qty    = 0;

    // ── Modal state ──────────────────────────────────────────────────────────
    public $showModal       = false;
    public $showManageModal = false;

    // ── Item search ──────────────────────────────────────────────────────────
    public $itemSearch       = '';
    public $showItemDropdown = false;

    // ── Filters ──────────────────────────────────────────────────────────────
    public $filterDate;
    public $filterStatus = '';

    // ── Lifecycle ────────────────────────────────────────────────────────────
    public function mount()
    {
        $this->filterDate   = now()->format('Y-m-d');
        $this->ordered_date = now()->format('Y-m-d');
        $this->ordered_time = now()->format('H:i');
    }

    // ── Item suggestions ─────────────────────────────────────────────────────
    public function getItemSuggestionsProperty()
    {
        if (strlen($this->itemSearch) < 1) return collect();

        return Item::where('name', 'like', '%' . $this->itemSearch . '%')
            ->limit(10)->get();
    }

    public function updatedItemSearch($value)
    {
        $this->item_name        = $value;
        $this->item_id          = null;
        $this->showItemDropdown = strlen($value) > 0;
    }

    public function selectItem($id, $name)
    {
        $this->item_id          = $id;
        $this->item_name        = $name;
        $this->itemSearch       = $name;
        $this->showItemDropdown = false;
    }

    // ── City list ─────────────────────────────────────────────────────────────
    public function getCitySuggestionsProperty()
    {
        $cities = $this->albaniaOnlineCities();

        if (strlen($this->citySearch) < 1) return collect($cities);

        return collect($cities)->filter(fn($c) =>
            str_contains(strtolower($c), strtolower($this->citySearch))
        )->values();
    }

    public function updatedCitySearch($value)
    {
        $this->city           = $value;
        $this->showCityDropdown = strlen($value) >= 0;
    }

    public function selectCity($name)
    {
        $this->city            = $name;
        $this->citySearch      = $name;
        $this->showCityDropdown = false;
    }

    private function albaniaOnlineCities(): array
    {
        return [
            'Caerleon',
            'Bridgewatch',
            'Martlock',
            'Lymhurst',
            'Fort Sterling',
            'Thetford',
            'Brecilien',
            'Black Market',
        ];
    }

    // ── Save / Update ────────────────────────────────────────────────────────
    public function save()
    {
        $this->validate([
            'item_name'    => 'required|string|max:255',
            'tier'         => 'required|string|max:10',
            'qty_ordered'  => 'required|integer|min:1',
            'cost_per'     => 'required|numeric|min:0',
            'enchantment'  => 'nullable|integer|min:0|max:4',
            'city'         => 'nullable|string|max:100',
            'ordered_date' => 'required|date',
            'ordered_time' => 'required',
        ]);

        if (! $this->item_id) {
            $item          = Item::firstOrCreate(['name' => $this->item_name]);
            $this->item_id = $item->id;
        }

        $setupFee = $this->cost_per * 0.025;

        $data = [
            'user_id'      => Auth::id(),
            'item_id'      => $this->item_id,
            'item_name'    => $this->item_name,
            'tier'         => $this->tier,
            'enchantment'  => $this->enchantment !== '' ? $this->enchantment : null,
            'city'         => $this->city ?: null,
            'qty_ordered'  => $this->qty_ordered,
            'cost_per'     => $this->cost_per,
            'setup_fee'    => $setupFee,
            'total_setup'  => $setupFee * $this->qty_ordered,
            'total_cost'   => $this->cost_per * $this->qty_ordered,
            'final_landed' => ($this->cost_per + $setupFee) * $this->qty_ordered,
            'ordered_date' => $this->ordered_date,
            'ordered_time' => $this->ordered_time,
        ];

        if ($this->editId) {
            BuyOrder::where('user_id', Auth::id())
                ->findOrFail($this->editId)
                ->update($data);
        } else {
            $data['ordered_at'] = now();
            $data['order_id']   = 'BO-' . now()->format('YmdHis') . '-' . str_pad(
                BuyOrder::where('user_id', Auth::id())
                    ->whereDate('ordered_date', $this->ordered_date)
                    ->count() + 1,
                4, '0', STR_PAD_LEFT
            );
            $data['status'] = 'Pending';
            BuyOrder::create($data);
        }

        $this->closeModal();
    }

    // ── Edit ─────────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $order = BuyOrder::where('user_id', Auth::id())->findOrFail($id);

        $this->editId       = $order->id;
        $this->item_id      = $order->item_id;
        $this->item_name    = $order->item_name;
        $this->itemSearch   = $order->item_name;
        $this->tier         = $order->tier;
        $this->enchantment  = $order->enchantment ?? '';
        $this->city         = $order->city ?? '';
        $this->citySearch   = $order->city ?? '';
        $this->qty_ordered  = $order->qty_ordered;
        $this->cost_per     = $order->cost_per;
        $this->ordered_date = $order->ordered_date->format('Y-m-d');
        $this->ordered_time = $order->ordered_time;
        $this->showModal    = true;
    }

    // ── Manage ───────────────────────────────────────────────────────────────
    public function manage($id)
    {
        // scope to user so they can't manage someone else's order
        $order = BuyOrder::where('user_id', Auth::id())->findOrFail($id);

        $this->manageId    = $id;
        $this->receive_qty = $order->qty_ordered - $order->qty_received; // prefill pending
        $this->showManageModal = true;
    }

    public function receiveStock()
    {
        // Scope to user_id — security fix
        $order     = BuyOrder::where('user_id', Auth::id())->findOrFail($this->manageId);
        $remaining = $order->qty_ordered - $order->qty_received;

        $this->validate([
            'receive_qty' => "required|integer|min:1|max:{$remaining}",
        ]);

        $order->qty_received += $this->receive_qty;

        if ($order->qty_received >= $order->qty_ordered) {
            $order->status       = 'Received';
            $order->completed_at = now();
            $order->received_time = now()->format('H:i:s');
        } elseif ($order->qty_received > 0) {
            $order->status = 'Partial';
        } else {
            $order->status = 'Pending';
        }

        $order->save();

        $this->showManageModal = false;
        session()->flash('success', 'Stock updated successfully.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    public function delete($id)
    {
        BuyOrder::where('user_id', Auth::id())->findOrFail($id)->delete();
    }

    // ── Status quick-update ───────────────────────────────────────────────────
    public function updateStatus($id, $status)
    {
        BuyOrder::where('user_id', Auth::id())
            ->findOrFail($id)
            ->update(['status' => $status]);
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
            'item_name', 'item_id', 'itemSearch', 'showItemDropdown',
            'tier', 'qty_ordered', 'cost_per', 'editId',
            'city', 'citySearch', 'showCityDropdown',
        ]);
        $this->enchantment  = '0';
        $this->ordered_date = now()->format('Y-m-d');
        $this->ordered_time = now()->format('H:i');
    }

    // ── Render ────────────────────────────────────────────────────────────────
    public function render()
    {
        $query = BuyOrder::where('user_id', Auth::id());

        if ($this->filterDate) {
            // FIX: was filtering on ordered_at, now matches ordered_date column
            $query->whereDate('ordered_date', $this->filterDate);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $orders   = $query->latest('ordered_date')->get();
        $allItems = Item::orderBy('name')->get();

        return view('livewire.buy-orders', [
            'orders'   => $orders,
            'allItems' => $allItems,
        ]);
    }
}