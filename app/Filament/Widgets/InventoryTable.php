<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryWriter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class InventoryTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Inventory at a glance';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table->query(Item::visibleTo(auth()->user())->with(['warehouse', 'inventoryGroup']))->columns([\Filament\Tables\Columns\Layout\Grid::make(['default' => 1, 'sm' => 2, 'xl' => 3])->schema([
            TextColumn::make('name')->wrap()->searchable()->weight('bold')->description(fn (Item $record) => $record->sku), TextColumn::make('warehouse.name')->description('Warehouse', position: 'above')->wrap()->label('Warehouse'), TextColumn::make('quantity')->description('Quantity', position: 'above')->wrap()->label('Qty')->sortable(), TextColumn::make('unit_price')->description('Unit value · USD', position: 'above')->wrap()->money('USD', locale: 'en_US')->label('Unit value (USD)')->sortable(), TextColumn::make('status')->description('Status', position: 'above')->wrap()->badge()->color(fn ($state) => $state === 'available' ? 'success' : 'warning'),
        ])])->filters([SelectFilter::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))])->recordActions([
            EditAction::make()->schema(fn ($schema) => ItemResource::form($schema))->using(fn (Item $record, array $data) => app(InventoryWriter::class)->save(auth()->user(), Item::class, $data, $record)), Action::make('label')->icon('heroicon-o-qr-code')->url(fn (Item $record) => route('items.label', $record))->openUrlInNewTab(),
        ])->defaultSort('updated_at', 'desc')->paginated([5, 10, 25])->defaultPaginationPageOption(5)->emptyStateHeading('Your vault starts with one piece')->emptyStateDescription('Add a warehouse, then scan a code or enter your first item.')->emptyStateActions([Action::make('start')->label('Add your first item')->url('/admin/quick-add')]);
    }
}
