<?php

namespace App\Policies;

use App\Models\User;

class WarehousePolicy extends InventoryPolicy
{
    public function create(User $u): bool
    {
        return $u->is_active && $u->can('warehouses.manage');
    }
}
