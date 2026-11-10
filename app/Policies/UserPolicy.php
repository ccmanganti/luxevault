<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->is_active && $u->can('staff.manage');
    }

    public function view(User $u, User $r): bool
    {
        return $this->viewAny($u) && ($u->isSuper() || ($r->owner_id === $u->id && $r->hasRole('staff')));
    }

    public function create(User $u): bool
    {
        return $this->viewAny($u);
    }

    public function update(User $u, User $r): bool
    {
        return $this->view($u, $r);
    }

    public function delete(User $u, User $r): bool
    {
        return false;
    }

    public function deleteAny(User $u): bool
    {
        return false;
    }
}
