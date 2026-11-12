<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryGroup extends Model
{
    use \App\Models\Concerns\HasInventoryPhotos;

    protected $fillable = ['photos', 'project_id', 'name', 'type', 'notes'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function scopeVisibleTo($q, User $u)
    {
        return $q->whereIn('project_id', Project::visibleTo($u)->select('id'));
    }
}
