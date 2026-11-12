<?php
namespace App\Filament\Pages;
use App\Models\Item;
use Filament\Pages\Page;
use Filament\Tables\{Table, Contracts\HasTable, Concerns\InteractsWithTable};
use Filament\Tables\Columns\{TextColumn, Layout\Stack};
use Filament\Actions\Action;
class SharedRecords extends Page implements HasTable {
    use InteractsWithTable;
    protected static string|\BackedEnum|null $navigationIcon='heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup='Collaboration';
    protected static ?string $title='Shared records';
    protected static ?int $navigationSort=0;
    protected string $view='filament.pages.shared-records';
    public function table(Table $table): Table {
        return $table->query(Item::whereHas('warehouse.owner',fn($q)=>$q->where('is_active',true))->whereHas('shares',fn($q)=>$q->active()->where('user_id',auth()->id()))->with(['warehouse','responsible'])->withCount(['handoffs as pending_handoffs'=>fn($q)=>$q->where('status','pending')->where('to_user_id',auth()->id())]))
            ->contentGrid(['default'=>1,'md'=>2,'xl'=>3])->columns([Stack::make([
                TextColumn::make('pending_handoffs')->formatStateUsing(fn($state)=>$state?'Handoff waiting for you':'')->badge()->color('warning'),TextColumn::make('name')->weight('bold')->searchable(),TextColumn::make('sku')->size('sm')->searchable(),TextColumn::make('warehouse.name')->label('Warehouse'),TextColumn::make('status')->formatStateUsing(fn($state)=>\App\Services\ItemOperations::STATUSES[$state]??$state)->badge(),
            ])])->recordActions([Action::make('open')->label('Open shared record')->url(fn(Item $record)=>ItemRecord::getUrl(['itemId'=>$record->id]))])->paginated([12,24])->defaultPaginationPageOption(12)->emptyStateHeading('Your shared items will appear here')->emptyStateDescription('Ask the item owner to give access using your Luxe Vault account email.');
    }
}
