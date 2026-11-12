<?php
namespace App\Support;
use App\Models\{Item, ItemCategory, User, Warehouse};
use Filament\Forms\Components\{Select, TextInput};
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Validator;
class AccountCategories {
    public const DEFAULTS = ['Furniture', 'Lighting', 'Art', 'Rugs', 'Accessories', 'Others'];
    public static function ownerId(User $actor, ?int $warehouseId = null): int {
        if ($warehouseId) return Warehouse::visibleTo($actor)->findOrFail($warehouseId)->owner_id;
        return $actor->owner_id ?: $actor->id;
    }
    public static function options(User $actor, ?int $warehouseId = null): array {
        $owner = self::ownerId($actor, $warehouseId);
        $names = collect(self::DEFAULTS)
            ->merge(ItemCategory::where('owner_id', $owner)->orderBy('name')->pluck('name'))
            ->merge(Item::whereHas('warehouse', fn ($q) => $q->where('owner_id', $owner))->distinct()->orderBy('category')->pluck('category'))
            ->filter()->unique(fn ($name) => mb_strtolower($name));
        return $names->mapWithKeys(fn ($name) => [$name => $name])->all();
    }
    public static function remember(int $ownerId, string $name): string {
        $name = preg_replace('/\s+/u', ' ', trim($name));
        Validator::make(['name' => $name], ['name' => 'required|string|max:100'])->validate();
        foreach (self::DEFAULTS as $default) if (mb_strtolower($default) === mb_strtolower($name) || ($default === 'Others' && $name === 'Other')) return $default;
        return ItemCategory::firstOrCreate(['owner_id' => $ownerId, 'normalized_name' => mb_strtolower($name)], ['name' => $name])->name;
    }
    public static function field(): Select {
        return Select::make('category')->label('Category')->required()->searchable()->default('Furniture')
            ->options(fn (Get $get) => self::options(auth()->user(), $get('warehouse_id')))
            ->createOptionForm([TextInput::make('name')->label('New category')->required()->maxLength(100)->helperText('Available only within this warehouse owner’s account.')])
            ->createOptionUsing(function (array $data, Get $get): string {
                abort_unless(auth()->user()->can('inventory.manage'), 403);
                return self::remember(self::ownerId(auth()->user(), $get('warehouse_id')), $data['name']);
            });
    }
}
