<?php
namespace App\Support;
use App\Models\{Item, Warehouse, Project, InventoryGroup};
use App\Services\InventoryWriter;
use Filament\Actions\{Action, ActionGroup, EditAction, DeleteAction};
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\{Get, Set};
use Illuminate\Database\Eloquent\Builder;
class ItemTable {
    public static function configure(Table $table): Table {
        return $table->modifyQueryUsing(fn (Builder $query) => $query->with(['warehouse','inventoryGroup.project']))
            ->extraAttributes(['class'=>'lv-items-table'])
            ->contentGrid(['default'=>1,'xl'=>2])
            ->columns([Stack::make([
                ViewColumn::make('name')->label('Item name')->view('tables.columns.item-summary')->searchable(['name','sku','barcode','location']),
            ])])->filters([
                Filter::make('place')->label('Location')->schema([
                    Select::make('warehouse')->label('Warehouse')->options(fn()=>Warehouse::visibleTo(auth()->user())->orderBy('name')->pluck('name','id'))->searchable()->live()->afterStateUpdated(function(Set $set){$set('project',null);$set('group',null);}),
                    Select::make('project')->label('Project')->options(fn(Get $get)=>Project::visibleTo(auth()->user())->when($get('warehouse'),fn($q,$id)=>$q->where('warehouse_id',$id))->orderBy('name')->pluck('name','id'))->searchable()->live()->afterStateUpdated(fn(Set $set)=>$set('group',null)),
                    Select::make('group')->label('Room / group')->options(fn(Get $get)=>InventoryGroup::visibleTo(auth()->user())->when($get('project'),fn($q,$id)=>$q->where('project_id',$id))->when($get('warehouse'),fn($q,$id)=>$q->whereHas('project',fn($p)=>$p->where('warehouse_id',$id)))->orderBy('name')->pluck('name','id'))->searchable(),
                ])->query(fn(Builder $query,array $data)=>$query
                    ->when($data['warehouse']??null,fn($q,$id)=>$q->where('warehouse_id',$id))
                    ->when($data['project']??null,fn($q,$id)=>$q->whereHas('inventoryGroup',fn($g)=>$g->where('project_id',$id)))
                    ->when($data['group']??null,fn($q,$id)=>$q->where('inventory_group_id',$id)))
                    ->indicateUsing(fn(array $data)=>array_filter([
                        !empty($data['warehouse'])?'Warehouse selected':null,
                        !empty($data['project'])?'Project selected':null,
                        !empty($data['group'])?'Room / group selected':null,
                    ])),
                SelectFilter::make('category')->options(fn()=>Item::visibleTo(auth()->user())->distinct()->orderBy('category')->pluck('category','category'))->searchable(),
                SelectFilter::make('status')->options(\App\Services\ItemOperations::STATUSES),
                Filter::make('order')->label('Sort by')->schema([
                    Select::make('by')->label('Sort by')->options(['recent'=>'Recently updated','name'=>'Name A–Z','quantity'=>'Quantity: high to low','value_high'=>'Value: high to low','value_low'=>'Value: low to high'])->default('recent')->selectablePlaceholder(false),
                ])->baseQuery(function(Builder $query,array $data){
                    [$column,$direction]=match($data['by']??'recent'){'name'=>['name','asc'],'quantity'=>['quantity','desc'],'value_high'=>['unit_price','desc'],'value_low'=>['unit_price','asc'],default=>['updated_at','desc']};
                    return $query->orderBy($column,$direction);
                }),
            ])->deferFilters(false)->filtersFormColumns(1)
            ->recordActions([
                Action::make('record')->label('Details')->url(fn(Item $record)=>\App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$record->id])),
                Action::make('investment')->label('Investment')->icon('heroicon-o-chart-bar')->url(fn(Item $record)=>\App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$record->id,'tab'=>'investment'])),
                Action::make('sharing')->label('Share & handoff')->icon('heroicon-o-user-group')->url(fn(Item $record)=>\App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$record->id,'tab'=>'history'])),
                ActionGroup::make([
                EditAction::make()->visible(fn(Item $record)=>auth()->user()->can('update',$record))->label('Edit')->schema(fn($schema)=>$schema->components(ItemForm::components()))->using(fn(Item $record,array $data)=>app(InventoryWriter::class)->save(auth()->user(),Item::class,$data,$record)),
                Action::make('label')->label('Label')->icon('heroicon-o-qr-code')->url(fn(Item $record)=>route('items.label',$record))->openUrlInNewTab(),
                DeleteAction::make()->visible(fn(Item $record)=>auth()->user()->can('delete',$record))->label('Delete item')->using(function(Item $record): bool { app(InventoryWriter::class)->delete(auth()->user(),$record); return true; }),
                ])->label('More')->tooltip('Edit, print label or delete')->icon('heroicon-o-ellipsis-horizontal'),
            ])->defaultSort('updated_at','desc')->defaultSortOptionLabel('Recently updated')->paginated([12,24,48])->defaultPaginationPageOption(12)
            ->emptyStateHeading('No items in this view')->emptyStateDescription('Choose another location, clear your filters, or add an item.');
    }
}
