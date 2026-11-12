<?php

namespace App\Services;

use App\Models\InventoryEvent;
use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryWriter
{
    public function save(User $actor, string $class, array $data, ?Model $record = null): Model
    {
        Gate::forUser($actor)->authorize($record ? 'update' : 'create', $record ?? $class);

        return DB::transaction(function () use ($actor, $class, $data, $record) {
            $record ??= new $class;
            $new = ! $record->exists;
            $rules = ['name' => 'required|string|max:255', 'notes' => 'nullable|string|max:10000', 'photos' => 'nullable|array|max:6', 'photos.*' => 'required|string|max:500'];
            if ($class === Warehouse::class) {
                $owner = $record->exists ? $record->owner : ($actor->isSuper() ? User::findOrFail($data['owner_id'] ?? $actor->id) : $actor);
                $owner = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
                if (! $owner->isOwner() && ! $owner->isSuper()) {
                    throw ValidationException::withMessages(['owner_id' => 'Choose a warehouse owner.']);
                }
                if ($new && $owner->plan === 'free' && ! $owner->isSuper() && $owner->ownedWarehouses()->exists()) {
                    throw ValidationException::withMessages(['name' => 'Free includes one warehouse. Premium unlocks multiple warehouses.']);
                }
                $record->owner_id = $owner->id;
                $rules['address'] = 'nullable|string|max:2000';
            } elseif (in_array($class, [Project::class, Item::class])) {
                $warehouse = Warehouse::visibleTo($actor)->findOrFail($data['warehouse_id'] ?? $record->warehouse_id);
                if ($record->exists && $class === Project::class && $record->warehouse_id !== $warehouse->id && $record->groups()->exists()) {
                    throw ValidationException::withMessages(['warehouse_id' => 'A project with groups cannot be moved.']);
                }
                if ($record->exists && $class === Item::class && $record->warehouse->owner_id !== $warehouse->owner_id) {
                    throw ValidationException::withMessages(['warehouse_id' => 'Choose a warehouse in the same account. Use shared records for external handoffs.']);
                }
                $data['warehouse_id'] = $warehouse->id;
                $rules['warehouse_id'] = 'required|integer';
                if ($class === Project::class) {
                    $rules['client_name'] = 'nullable|string|max:255';
                }
                if ($class === Item::class) {
                    if ($record->exists && empty($data['sku'])) {
                        $data['sku'] = $record->sku;
                    }
                    if (! array_key_exists('inventory_group_id', $data) && $record->exists) {
                        $data['inventory_group_id'] = $record->inventory_group_id;
                    }
                    if (! empty($data['inventory_group_id'])) {
                        $group = InventoryGroup::visibleTo($actor)->findOrFail($data['inventory_group_id']);
                        if ($group->project->warehouse_id !== $warehouse->id) {
                            throw ValidationException::withMessages(['inventory_group_id' => 'The group must belong to this warehouse.']);
                        }
                    }
                    $rules += ['inventory_group_id' => 'nullable|integer', 'sku' => ['nullable', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('items', 'sku')->ignore($record->id)], 'barcode' => 'nullable|string|max:120', 'category' => 'required|string|max:100', 'location' => 'nullable|string|max:255', 'condition' => Rule::in(['good', 'fair', 'damaged']), 'status' => Rule::in(array_keys(ItemOperations::STATUSES)), 'quantity' => 'required|integer|min:0|max:1000000', 'low_stock_threshold' => 'required|integer|min:0|max:1000000', 'unit_cost' => 'required|numeric|min:0|max:9999999999.99', 'unit_price' => 'required|numeric|min:0|max:9999999999.99'];
                }
            } elseif ($class === InventoryGroup::class) {
                $project = Project::visibleTo($actor)->findOrFail($data['project_id']);
                if ($record->exists && $record->project_id !== $project->id && $record->items()->exists()) {
                    throw ValidationException::withMessages(['project_id' => 'A group containing items cannot be moved.']);
                }
                $rules += ['project_id' => 'required|integer', 'type' => Rule::in(['room', 'custom'])];
                $rules['name'] = ['required', 'string', 'max:255', Rule::unique('inventory_groups', 'name')->where('project_id', $project->id)->ignore($record->id)];
            }
            $valid = Validator::make($data, $rules)->validate();
            if ($class === Item::class) {
                $valid['category'] = \App\Support\AccountCategories::remember($warehouse->owner_id, $valid['category']);
            }
            if (array_key_exists('photos', $valid)) {
                $disk = \Illuminate\Support\Facades\Storage::disk('local');
                foreach ($valid['photos'] ?? [] as $photo) {
                    $ownedUpload = str_starts_with($photo, 'vault-photos/'.$actor->id.'/');
                    $existingPhoto = in_array($photo, $record->photos ?? [], true);
                    if ((! $ownedUpload && ! $existingPhoto) || ! preg_match('/^vault-photos\/[0-9]+\/[A-Za-z0-9]+\.(jpg|jpeg|png|webp)$/i', $photo) || str_contains($photo, '\\') || ! $disk->exists($photo)
                        || ! in_array($disk->mimeType($photo), ['image/jpeg', 'image/png', 'image/webp'], true)
                        || $disk->size($photo) > 5 * 1024 * 1024) {
                        throw ValidationException::withMessages(['photos' => 'Choose a valid photo uploaded for this record (JPG, PNG or WebP, up to 5 MB).']);
                    }
                }
                $valid['photos'] = array_values($valid['photos'] ?? []);
            }
            $record->fill($valid);
            $changes = $record->getDirty();
            if ($class === Item::class) {
                if ($new) $record->acquisition_total = round((float)$record->unit_cost * (int)$record->quantity, 2);
                $record->revision = (int)$record->revision + 1;
            }
            $record->save();
            if ($class === Item::class) {
                InventoryEvent::create(['warehouse_id' => $record->warehouse_id, 'user_id' => $actor->id, 'item_name' => $record->name, 'action' => $new ? 'Added' : 'Updated', 'changes' => $changes]);
                $messages = [];
                if (isset($changes['status'])) $messages[] = 'Status: '.(ItemOperations::STATUSES[$record->status] ?? $record->status);
                if (isset($changes['condition'])) $messages[] = 'Condition: '.$record->condition;
                if (isset($changes['warehouse_id']) || array_key_exists('inventory_group_id', $changes) || array_key_exists('location', $changes)) $messages[] = 'Location updated';
                if (array_key_exists('quantity', $changes)) $messages[] = 'Quantity: '.$record->quantity;
                if ($messages) app(ItemOperations::class)->activity($actor, $record, $new ? 'created' : 'updated', implode(' · ', $messages));
            }

            return $record;
        });
    }

    public function delete(User $actor, Model $record): void
    {
        Gate::forUser($actor)->authorize('delete', $record);
        DB::transaction(function () use ($actor, $record) {
            $hasChildren = match (true) {
                $record instanceof Warehouse => $record->items()->exists() || $record->projects()->exists(),$record instanceof Project => $record->groups()->exists(),$record instanceof InventoryGroup => $record->items()->exists(),default => false
            };
            if ($hasChildren) {
                throw ValidationException::withMessages(['record' => 'Remove or reassign the contents before deleting this record.']);
            }
            if ($record instanceof Item) {
                InventoryEvent::create(['warehouse_id' => $record->warehouse_id, 'user_id' => $actor->id, 'item_name' => $record->name, 'action' => 'Deleted']);
            }
            $record->delete();
        });
    }
}
