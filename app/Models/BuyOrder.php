<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyOrder extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'item_id', 'item_name', 'tier', 'enchantment', 'city',
        'qty_ordered', 'qty_received', 'qty_synced',
        'cost_per', 'setup_fee', 'total_setup', 'total_cost', 'final_landed',
        'status',
        'ordered_at', 'ordered_date', 'ordered_time',
        'completed_at', 'received_time',
    ];

    protected $casts = [
        'ordered_date' => 'date',
        'ordered_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function item()      { return $this->belongsTo(Item::class); }
    public function salesOrders() { return $this->hasMany(SalesOrder::class); }

    public function getQtyPendingAttribute(): int
    {
        return max(0, $this->qty_ordered - $this->qty_received);
    }
}