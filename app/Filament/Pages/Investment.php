<?php
namespace App\Filament\Pages;

use App\Models\Item;
use App\Support\ItemTable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\{Table, Contracts\HasTable, Concerns\InteractsWithTable};

class Investment extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Investment';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Item investment';
    protected string $view = 'filament.pages.investment';

    public static function canAccess(): bool { return auth()->user()?->can('inventory.view') ?? false; }

    public function getSubheading(): string
    {
        return 'Choose a piece to review costs, usage, revenue and its next move.';
    }

    public function table(Table $table): Table
    {
        return ItemTable::configure($table)->query(Item::visibleTo(auth()->user()))
            ->recordActions([
                Action::make('investment')->label('Costs, usage & decisions')->icon('heroicon-o-chart-bar')
                    ->url(fn(Item $record)=>ItemRecord::getUrl(['itemId'=>$record->id,'tab'=>'investment'])),
            ])->emptyStateHeading('Your investment records start with an item')
            ->emptyStateDescription('Add an item to your inventory, then return here to log its costs and usage.');
    }
}
