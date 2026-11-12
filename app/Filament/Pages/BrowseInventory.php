<?php
namespace App\Filament\Pages;
use App\Models\{Warehouse, Project, InventoryGroup, Item};
use App\Filament\Resources\ItemResource;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
class BrowseInventory extends Page implements HasTable {
    use InteractsWithTable;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Browse inventory';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Browse your vault';
    protected string $view = 'filament.pages.browse-inventory';
    #[Url] public ?int $warehouseId = null;
    #[Url] public ?int $projectId = null;
    #[Url] public ?int $groupId = null;
    public static function canAccess(): bool { return auth()->user()?->can('inventory.view') ?? false; }
    public function location(): array {
        $user=auth()->user();
        $warehouse=$this->warehouseId?Warehouse::visibleTo($user)->findOrFail($this->warehouseId):null;
        $project=$this->projectId?Project::visibleTo($user)->where('warehouse_id',$warehouse?->id)->findOrFail($this->projectId):null;
        $group=$this->groupId?InventoryGroup::visibleTo($user)->where('project_id',$project?->id)->findOrFail($this->groupId):null;
        return [$warehouse,$project,$group];
    }
    public function chooseWarehouse(?int $id): void {
        if($id) Warehouse::visibleTo(auth()->user())->findOrFail($id);
        $this->warehouseId=$id;$this->projectId=null;$this->groupId=null;$this->resetTable();
    }
    public function chooseProject(int $id): void {
        $project=Project::visibleTo(auth()->user())->findOrFail($id);
        $this->warehouseId=$project->warehouse_id;$this->projectId=$id;$this->groupId=null;$this->resetTable();
    }
    public function chooseGroup(int $id): void {
        $group=InventoryGroup::visibleTo(auth()->user())->with('project')->findOrFail($id);
        $this->warehouseId=$group->project->warehouse_id;$this->projectId=$group->project_id;$this->groupId=$id;$this->resetTable();
    }
    public function table(Table $table): Table {
        return ItemResource::table($table)->query(function(){
            [$warehouse,$project,$group]=$this->location();
            return Item::visibleTo(auth()->user())
                ->when($warehouse,fn($q)=>$q->where('warehouse_id',$warehouse->id))
                ->when($project,fn($q)=>$q->whereHas('inventoryGroup',fn($g)=>$g->where('project_id',$project->id)))
                ->when($group,fn($q)=>$q->where('inventory_group_id',$group->id));
        });
    }
    protected function getHeaderActions(): array {
        return [ItemResource::createAction()->model(Item::class)->label('Add item')->schema(fn($schema)=>ItemResource::form($schema))->fillForm(fn()=>['warehouse_id'=>$this->warehouseId,'inventory_group_id'=>$this->groupId,'quantity'=>1,'low_stock_threshold'=>0,'unit_cost'=>0,'unit_price'=>0,'category'=>'Furniture','condition'=>'good','status'=>'available'])->visible(fn()=>auth()->user()->can('inventory.manage'))];
    }
    protected function getViewData(): array {
        [$warehouse,$project,$group]=$this->location();
        return [
            'selectedWarehouse'=>$warehouse,'selectedProject'=>$project,'selectedGroup'=>$group,
            'warehouses'=>Warehouse::visibleTo(auth()->user())->withCount('items')->orderBy('name')->get(),
            'projects'=>$warehouse?Project::visibleTo(auth()->user())->where('warehouse_id',$warehouse->id)->orderBy('name')->get():collect(),
            'groups'=>$project?InventoryGroup::visibleTo(auth()->user())->where('project_id',$project->id)->withCount('items')->orderBy('name')->get():collect(),
        ];
    }
}
