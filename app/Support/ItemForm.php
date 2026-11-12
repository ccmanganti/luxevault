<?php
namespace App\Support;
use App\Models\{Warehouse, InventoryGroup};
use Filament\Forms\Components\{Select, TextInput, Textarea};
use Filament\Schemas\Components\{Tabs, Tabs\Tab};
use Filament\Schemas\Components\Utilities\{Get, Set};
class ItemForm {
    public static function components(): array {
        return [Tabs::make('Item details')->extraAttributes(['class' => 'lv-item-editor'])->tabs([
            Tab::make('Essentials')->icon('heroicon-o-cube')->schema([
                TextInput::make('name')->label('Item name')->required()->maxLength(255)->columnSpanFull(),
                AccountCategories::field(),
                Select::make('status')->options(['available'=>'Available','reserved'=>'Reserved','installed'=>'Installed','repair'=>'Repair'])->default('available')->required(),
                Select::make('condition')->options(['good'=>'Good','fair'=>'Fair','damaged'=>'Damaged'])->default('good')->required(),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ])->columns(2),
            Tab::make('Placement')->icon('heroicon-o-map-pin')->schema([
                Select::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name','id'))->searchable()->required()->live()->afterStateUpdated(fn (Set $set) => $set('inventory_group_id', null)),
                Select::make('inventory_group_id')->label('Project / room or group')->options(fn (Get $get) => InventoryGroup::visibleTo(auth()->user())->whereHas('project',fn ($q) => $q->where('warehouse_id',$get('warehouse_id')))->with('project')->get()->mapWithKeys(fn ($g)=>[$g->id=>$g->project->name.' / '.$g->name]))->searchable()->placeholder('Unassigned')->helperText('Manage warehouses, projects and rooms from Resource management.'),
                TextInput::make('location')->label('Shelf / location')->placeholder('Aisle A · Shelf 02')->maxLength(255)->columnSpanFull(),
            ])->columns(2),
            Tab::make('Stock & value')->icon('heroicon-o-banknotes')->schema([
                TextInput::make('quantity')->integer()->minValue(0)->default(1)->required(),
                TextInput::make('low_stock_threshold')->label('Low stock at')->integer()->minValue(0)->default(0)->required(),
                TextInput::make('unit_cost')->label('Unit cost')->prefix('USD $')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('unit_price')->label('Unit value')->prefix('USD $')->numeric()->minValue(0)->default(0)->required(),
            ])->columns(2),
            Tab::make('Photos')->icon('heroicon-o-photo')->schema([InventoryPhotoField::make()]),
            Tab::make('Labels')->icon('heroicon-o-qr-code')->schema([
                TextInput::make('sku')->label('SKU')->placeholder('Generated automatically')->maxLength(80)->unique(ignoreRecord:true),
                TextInput::make('barcode')->label('Existing barcode')->maxLength(120),
            ])->columns(2),
        ])->columnSpanFull()];
    }
}
