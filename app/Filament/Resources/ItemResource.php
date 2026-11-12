<?php

namespace App\Filament\Resources;

use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemResource extends BaseResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255)->columnSpanFull(), Select::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))->required()->live()->afterStateUpdated(fn (Set $set) => $set('inventory_group_id', null)), Select::make('inventory_group_id')->label('Project / group')->options(fn (Get $get) => InventoryGroup::visibleTo(auth()->user())->whereHas('project', fn ($q) => $q->where('warehouse_id', $get('warehouse_id')))->with('project')->get()->mapWithKeys(fn ($g) => [$g->id => $g->project->name.' / '.$g->name]))->searchable(), TextInput::make('sku')->label('SKU')->placeholder('Generated automatically')->maxLength(80)->unique(ignoreRecord: true), TextInput::make('barcode')->label('Existing barcode')->maxLength(120), Select::make('category')->options(['Furniture' => 'Furniture', 'Lighting' => 'Lighting', 'Art' => 'Art', 'Rugs' => 'Rugs', 'Accessories' => 'Accessories', 'Other' => 'Other'])->default('Furniture')->required(), TextInput::make('location')->placeholder('Aisle A · Shelf 02')->maxLength(255), Select::make('condition')->options(['good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged'])->default('good')->required(), Select::make('status')->options(['available' => 'Available', 'reserved' => 'Reserved', 'installed' => 'Installed', 'repair' => 'Repair'])->default('available')->required(), TextInput::make('quantity')->integer()->minValue(0)->default(1)->required(), TextInput::make('low_stock_threshold')->label('Low stock at')->integer()->minValue(0)->default(0)->required(), TextInput::make('unit_cost')->label('Unit cost')->prefix(config('luxe.currency'))->numeric()->minValue(0)->default(0)->required(), TextInput::make('unit_price')->label('Unit value / price')->prefix(config('luxe.currency'))->numeric()->minValue(0)->default(0)->required(), Textarea::make('notes')->rows(2)->columnSpanFull()])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->contentGrid(['default' => 1, 'md' => 2, '2xl' => 3])->columns([\Filament\Tables\Columns\Layout\Grid::make(['default' => 2])->schema([TextColumn::make('name')->columnSpanFull()->wrap()->searchable(['name', 'sku', 'barcode'])->weight('bold')->description(fn (Item $r) => $r->sku), TextColumn::make('warehouse.name')->description('Warehouse', position: 'above')->wrap()->badge(), TextColumn::make('inventoryGroup.name')->description('Group', position: 'above')->wrap()->label('Group')->placeholder('Unassigned'), TextColumn::make('quantity')->description('Quantity', position: 'above')->wrap()->sortable(), TextColumn::make('unit_price')->description('Unit value · USD', position: 'above')->wrap()->label('Unit value (USD)')->money('USD', locale: 'en_US')->sortable(), TextColumn::make('status')->description('Status', position: 'above')->wrap()->badge()->color(fn ($state) => match ($state) {
            'available' => 'success','repair' => 'danger',default => 'warning'
        }), TextColumn::make('category')->description('Category', position: 'above')->wrap(), TextColumn::make('location')->description('Location', position: 'above')->wrap()->searchable()])])->recordActions([...static::recordActions(), Action::make('label')->icon('heroicon-o-qr-code')->url(fn (Item $record) => route('items.label', $record))->openUrlInNewTab()])->filters([SelectFilter::make('status')->options(['available' => 'Available', 'reserved' => 'Reserved', 'installed' => 'Installed', 'repair' => 'Repair']), SelectFilter::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))])->defaultSort('created_at', 'desc')->emptyStateHeading('Your items will appear here')->emptyStateDescription('Add your first record to start organizing your vault.');
    }

    public static function getPages(): array
    {
        return ['index' => ItemResource\Pages\ManageItems::route('/')];
    }
}
