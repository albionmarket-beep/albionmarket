<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrder extends Model
{
    protected $fillable = [
        'user_id',
        'sales_id',
        'buy_order_id',

        'item_name',
        'tier',

        'qty',
        'qty_sold',

        'sell_price',
        'premium_tax',
        'setup_fee',

        'total_rev',
        'avg_cost',
        'total_cost',
        'profit',

        'status',
        'completed_at',

        'listed_date',
        'listed_time',

        'sold_date',
        'sold_time',
    ];

    protected $casts = [
        'listed_date' => 'date',
        'sold_date'   => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(BuyOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Computed helpers ──────────────────────────────────────────────────────

    /** Landed cost per unit from the linked buy order */
    public function getLandedCostPerUnitAttribute(): float
    {
        if (! $this->buyOrder) return 0;
        $bo = $this->buyOrder;
        return $bo->qty_ordered > 0
            ? $bo->final_landed / $bo->qty_ordered
            : 0;
    }

    /** Net per unit after Albion taxes (4% premium + 2.5% setup) */
    public function getNetPerUnitAttribute(): float
    {
        return $this->sell_price * (1 - 0.04 - 0.025);
    }
}