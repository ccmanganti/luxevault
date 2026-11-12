<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Item extends Model
{
    use \App\Models\Concerns\HasInventoryPhotos;

    protected $fillable = ['photos', 'warehouse_id', 'inventory_group_id', 'name', 'sku', 'barcode', 'category', 'location', 'condition', 'status', 'quantity', 'low_stock_threshold', 'unit_cost', 'unit_price', 'notes'];

    protected function casts(): array
    {
        return ['acquisition_total'=>'decimal:2','acquired_on'=>'date','repair_estimate'=>'decimal:2','reuse_revenue_estimate'=>'decimal:2','resale_estimate'=>'decimal:2','revision'=>'integer','responsible_user_id'=>'integer','unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'quantity' => 'integer', 'low_stock_threshold' => 'integer'];
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

    public function shares() { return $this->hasMany(ItemShare::class); }
    public function entries() { return $this->hasMany(ItemEntry::class); }
    public function activities() { return $this->hasMany(ItemActivity::class); }
    public function handoffs() { return $this->hasMany(ItemHandoff::class); }
    public function responsible() { return $this->belongsTo(User::class, 'responsible_user_id'); }

    public function scopeVisibleTo($q, User $u)
    {
        return $q->whereIn('warehouse_id', Warehouse::visibleTo($u)->select('id'));
    }
}
