<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'address', 'notes'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'warehouse_user');
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function scopeVisibleTo($q, User $u)
    {
        if (! $u->is_active) {
            return $q->whereRaw('1=0');
        }
        if ($u->isSuper()) {
            return $q;
        }
        if ($u->isOwner()) {
            return $q->where('owner_id', $u->id);
        }
        if (! $u->owner?->is_active || $u->owner?->plan !== 'premium') {
            return $q->whereRaw('1=0');
        }

        return $q->where('owner_id', $u->owner_id)->whereHas('staff', fn ($s) => $s->whereKey($u->id));
    }
}
