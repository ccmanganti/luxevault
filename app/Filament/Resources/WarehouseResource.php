<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseResource extends BaseResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Warehouse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('owner_id')->label('Warehouse owner')->options(fn () => User::role('warehouse_owner')->pluck('name', 'id'))->searchable()->visible(fn () => auth()->user()->isSuper())->required(fn () => auth()->user()->isSuper())->disabled(fn ($record) => $record !== null), TextInput::make('name')->required()->maxLength(255), Textarea::make('address')->rows(2), Textarea::make('notes')->rows(2)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([\Filament\Tables\Columns\Layout\Grid::make(['default' => 1, 'sm' => 2, 'xl' => 3])->schema([TextColumn::make('name')->wrap()->searchable()->weight('bold'), TextColumn::make('owner.name')->description('Owner', position: 'above')->wrap()->label('Owner'), TextColumn::make('items_count')->description('Items', position: 'above')->wrap()->counts('items')->label('Item records'), TextColumn::make('address')->description('Address', position: 'above')->wrap()->limit(40)])])->recordActions([...static::recordActions()])->defaultSort('created_at', 'desc')->emptyStateHeading('Your warehouses will appear here')->emptyStateDescription('Add your first record to start organizing your vault.');
    }

    public static function getPages(): array
    {
        return ['index' => WarehouseResource\Pages\ManageWarehouses::route('/')];
    }
}
