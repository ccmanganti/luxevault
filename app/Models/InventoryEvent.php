<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'changes' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo($q, User $u)
    {
        return $q->whereIn('warehouse_id', Warehouse::visibleTo($u)->select('id'));
    }
}
