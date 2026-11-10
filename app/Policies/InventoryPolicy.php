<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InventoryPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->is_active && $u->can('inventory.view');
    }

    public function view(User $u, Model $r): bool
    {
        return $this->viewAny($u) && $r->newQuery()->visibleTo($u)->whereKey($r->id)->exists();
    }

    public function create(User $u): bool
    {
        return $u->is_active && $u->can('inventory.manage');
    }

    public function update(User $u, Model $r): bool
    {
        return $this->create($u) && $this->view($u, $r);
    }

    public function delete(User $u, Model $r): bool
    {
        return ($u->isOwner() || $u->isSuper()) && $this->update($u, $r);
    }

    public function deleteAny(User $u): bool
    {
        return false;
    }
}
