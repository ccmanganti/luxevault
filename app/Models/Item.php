<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Item extends Model
{
    protected $fillable = ['warehouse_id', 'inventory_group_id', 'name', 'sku', 'barcode', 'category', 'location', 'condition', 'status', 'quantity', 'low_stock_threshold', 'unit_cost', 'unit_price', 'notes'];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'quantity' => 'integer', 'low_stock_threshold' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            $item->qr_code = (string) Str::uuid();
            $item->sku = $item->sku ?: 'LV-'.Str::upper(Str::random(10));
        });
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryGroup()
    {
        return $this->belongsTo(InventoryGroup::class);
    }

    public function scopeVisibleTo($q, User $u)
    {
        return $q->whereIn('warehouse_id', Warehouse::visibleTo($u)->select('id'));
    }
}
