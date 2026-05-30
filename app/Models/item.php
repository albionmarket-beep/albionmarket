<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['name', 'category', 'description'];

    public function buyOrders()
    {
        return $this->hasMany(BuyOrder::class);
    }
}