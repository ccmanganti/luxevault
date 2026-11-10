<?php

namespace App\Services;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountWriter
{
    public function save(User $actor, array $data, ?User $user = null): User
    {
        Gate::forUser($actor)->authorize($user ? 'update' : 'create', $user ?? User::class);

        return DB::transaction(function () use ($actor, $data, $user) {
            $new = ! $user;
            $user = $user ? User::whereKey($user->id)->lockForUpdate()->firstOrFail() : new User;
            $role = $actor->isSuper() ? ($data['role'] ?? 'staff') : 'staff';
            // Account identity is immutable after creation; membership can change safely.
            if (! $new) {
                $role = $user->getRoleNames()->first();
            }
            if (! in_array($role, ['superadmin', 'warehouse_owner', 'staff'])) {
                throw ValidationException::withMessages(['role' => 'Choose a supported role.']);
            }
            $rules = ['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'password' => [$new ? 'required' : 'nullable', 'string', 'min:12'], 'is_active' => 'boolean'];
            $valid = Validator::make($data, $rules)->validate();
            $owner = null;
            if ($role === 'staff') {
                $owner = $new ? ($actor->isSuper() ? User::findOrFail($data['owner_id'] ?? 0) : $actor) : $user->owner;
                $owner = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
                if (! $owner->isOwner() || $owner->plan !== 'premium' || ! $owner->is_active) {
                    throw ValidationException::withMessages(['name' => 'Guest staff require an active Premium warehouse owner.']);
                }
                $ids = array_unique($data['warehouse_ids'] ?? []);
                if (Warehouse::where('owner_id', $owner->id)->whereIn('id', $ids)->count() !== count($ids)) {
                    throw ValidationException::withMessages(['warehouse_ids' => 'Choose warehouses belonging to this owner.']);
                }
            }
            if ($user->id === $actor->id && ($data['is_active'] ?? true) == false) {
                throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
            }
            if (empty($valid['password'])) {
                unset($valid['password']);
            }
            $active = $valid['is_active'] ?? true;
            unset($valid['is_active']);
            $user->fill($valid);
            $user->is_active = $active;
            $user->owner_id = $owner?->id;
            if ($actor->isSuper() && $role !== 'staff') {
                $plan = $data['plan'] ?? $user->plan ?? 'free';
                if (! in_array($plan, ['free', 'premium'])) {
                    abort(422);
                }
                if (! $new && $plan === 'free' && ($user->ownedWarehouses()->count() > 1 || User::where('owner_id', $user->id)->where('is_active', true)->exists())) {
                    throw ValidationException::withMessages(['plan' => 'Reduce to one warehouse and deactivate guest accounts before downgrading.']);
                }
                $user->plan = $plan;
            }
            $user->save();
            if ($new) {
                $user->syncRoles($role);
            }
            if ($role === 'staff') {
                $user->warehouses()->sync($ids);
            }

            return $user;
        });
    }
}
