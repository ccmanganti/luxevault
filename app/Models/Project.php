<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['warehouse_id', 'name', 'client_name', 'notes'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function groups()
    {
        return $this->hasMany(InventoryGroup::class);
    }

    public function scopeVisibleTo($q, User $u)
    {
        return $q->whereIn('warehouse_id', Warehouse::visibleTo($u)->select('id'));
    }
}
