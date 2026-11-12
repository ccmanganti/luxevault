<?php
namespace App\Filament\Concerns;
use App\Models\{Item, Warehouse, InventoryGroup};
use App\Services\ItemOperations;
use Filament\Actions\Action;
use Filament\Forms\Components\{TextInput, Textarea, Select, DatePicker, FileUpload, Hidden};
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
trait InteractsWithItemActions {
    protected ?Item $operationItem = null;
    abstract protected function currentItem(): Item;
    protected function operations(): ItemOperations { return app(ItemOperations::class); }
    protected function done(string $title): void { $this->operationItem=null; Notification::make()->title($title)->success()->send(); }
    protected function internalItem(): Item { $item=$this->currentItem();abort_unless($this->operations()->internal(auth()->user(),$item),403);return $item; }
    protected function cachedRecord(int $id): Item { if (!$this->operationItem || $this->operationItem->id !== $id) $this->operationItem=$this->operations()->record(auth()->user(),$id);return $this->operationItem; }
    protected function canManageItem(): bool { $i=$this->currentItem();return $this->operations()->internal(auth()->user(),$i) && auth()->user()->can('update',$i); }
    public function statusAction(): Action {
        return Action::make('status')->label('Status & condition')->icon('heroicon-o-check-circle')->visible(fn()=>$this->canManageItem())
            ->fillForm(fn()=>$this->currentItem()->only(['status','condition','revision']))->schema([
                Select::make('status')->options(ItemOperations::STATUSES)->required(),Select::make('condition')->options(['good'=>'Good','fair'=>'Fair','damaged'=>'Damaged'])->required(),Hidden::make('revision'),
            ])->action(function(array $data){$this->operations()->quickUpdate(auth()->user(),$this->currentItem()->id,(int)$data['revision'],$data);$this->done('Item status updated');});
    }
    public function moveAction(): Action {
        return Action::make('move')->label('Move item')->icon('heroicon-o-arrows-right-left')->visible(fn()=>$this->canManageItem())
            ->fillForm(fn()=>$this->currentItem()->only(['warehouse_id','inventory_group_id','location','revision']))->schema([
                Select::make('warehouse_id')->label('Warehouse')->options(fn()=>Warehouse::visibleTo(auth()->user())->where('owner_id',$this->currentItem()->warehouse->owner_id)->pluck('name','id'))->required()->searchable()->live()->afterStateUpdated(fn(Set $set)=>$set('inventory_group_id',null)),
                Select::make('inventory_group_id')->label('Project / room or group')->options(fn(Get $get)=>InventoryGroup::visibleTo(auth()->user())->whereHas('project',fn($q)=>$q->where('warehouse_id',$get('warehouse_id')))->with('project')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->project->name.' / '.$g->name]))->searchable()->placeholder('Unassigned'),
                TextInput::make('location')->label('Shelf / location')->maxLength(255),Hidden::make('revision'),
            ])->action(function(array $data){$this->operations()->quickUpdate(auth()->user(),$this->currentItem()->id,(int)$data['revision'],$data);$this->done('Item moved');});
    }
    public function stockAction(): Action {
        return Action::make('stock')->label('Adjust quantity')->icon('heroicon-o-cube')->visible(fn()=>$this->canManageItem())
            ->fillForm(fn()=>$this->currentItem()->only(['quantity','revision']))->schema([TextInput::make('quantity')->label('New quantity')->integer()->minValue(0)->maxValue(1000000)->required(),Hidden::make('revision')])
            ->action(function(array $data){$this->operations()->quickUpdate(auth()->user(),$this->currentItem()->id,(int)$data['revision'],$data);$this->done('Quantity updated');});
    }
    public function noteAction(): Action {
        return Action::make('note')->label('Add update / evidence')->icon('heroicon-o-paper-clip')->visible(fn()=>$this->operations()->canContribute(auth()->user(),$this->currentItem()))
            ->modalDescription('Visible to everyone with access to this item. Add receiving photos, repair evidence or installation notes.')->schema([
                Textarea::make('body')->label('Update')->rows(3)->required()->maxLength(5000),
                FileUpload::make('attachments')->label('Evidence')->disk('local')->visibility('private')->directory(fn()=>'item-evidence/'.auth()->id())->acceptedFileTypes(['image/jpeg','image/png','image/webp','application/pdf'])->multiple()->maxFiles(4)->maxSize(5120)->preventFilePathTampering()->helperText('Up to four photos or PDFs, 5 MB each.'),
            ])->action(function(array $data){$this->operations()->addNote(auth()->user(),$this->currentItem(),$data);$this->done('Update added');});
    }
    public function entryAction(): Action {
        return Action::make('entry')->label('Log cost, use or revenue')->icon('heroicon-o-plus')->visible(fn()=>$this->canManageItem())->fillForm(fn()=>['occurred_on'=>now()->toDateString(),'uses'=>1,'days'=>0,'request_key'=>(string)Str::uuid()])
            ->modalDescription('Private to your account. Amounts are totals for this item record, in USD.')->schema([
                Select::make('kind')->label('What are you recording?')->options(['usage'=>'Usage','revenue'=>'Revenue','storage'=>'Storage cost','repair'=>'Repair cost','other_cost'=>'Other cost'])->required()->live(),
                DatePicker::make('occurred_on')->label('Date')->required()->maxDate(now()),
                TextInput::make('amount')->prefix('USD $')->numeric()->minValue(0.01)->maxValue(9999999999.99)->required(fn(Get $get)=>$get('kind')!=='usage')->visible(fn(Get $get)=>$get('kind')!=='usage'),
                TextInput::make('uses')->label('Number of uses')->integer()->minValue(1)->maxValue(1000000)->visible(fn(Get $get)=>$get('kind')==='usage'),
                TextInput::make('days')->label('Days in use')->integer()->minValue(0)->maxValue(1000000)->visible(fn(Get $get)=>$get('kind')==='usage'),
                Textarea::make('notes')->label('Project / reference / notes')->rows(2)->maxLength(2000),Hidden::make('request_key'),
            ])->action(function(array $data){$this->operations()->addEntry(auth()->user(),$this->currentItem(),$data);$this->done('Entry recorded');});
    }
    public function investmentAction(): Action {
        return Action::make('investment')->label('Acquisition & estimates')->visible(fn()=>$this->canManageItem())
            ->fillForm(fn()=>$this->internalItem()->only(['acquisition_total','acquired_on','repair_estimate','reuse_revenue_estimate','resale_estimate']))
            ->modalDescription('Use totals for the complete item record. Acquisition starts as unit cost × original quantity; later stock changes do not rewrite it. Estimates are optional.')->schema([
                TextInput::make('acquisition_total')->label('Total acquisition cost')->prefix('USD $')->numeric()->minValue(0)->maxValue(99999999999999.99)->required(),DatePicker::make('acquired_on')->label('Acquired on')->maxDate(now()),
                TextInput::make('repair_estimate')->label('Estimated repair cost')->prefix('USD $')->numeric()->minValue(0)->maxValue(9999999999.99),
                TextInput::make('reuse_revenue_estimate')->label('Expected revenue from next use')->prefix('USD $')->numeric()->minValue(0)->maxValue(9999999999.99),
                TextInput::make('resale_estimate')->label('Estimated resale value, as-is')->prefix('USD $')->numeric()->minValue(0)->maxValue(9999999999.99),
            ])->action(function(array $data){$this->operations()->saveInvestment(auth()->user(),$this->currentItem(),$data);$this->done('Investment details saved');});
    }
    public function voidEntryAction(): Action {
        return Action::make('voidEntry')->label('Correct entry')->visible(fn()=>$this->canManageItem())->modalDescription('The original entry stays in history but is excluded from totals. Add a replacement entry if needed.')->schema([TextInput::make('reason')->required()->maxLength(255)])
            ->action(function(array $data,array $arguments){$this->operations()->voidEntry(auth()->user(),$this->currentItem(),(int)($arguments['entryId']??0),$data['reason']);$this->done('Entry excluded from totals');});
    }
    public function decisionAction(): Action {
        return Action::make('decision')->label('Record a decision')->visible(fn()=>$this->canManageItem())->modalDescription('Shared with collaborators. This records the plan; use Status & condition when the item is actually repaired, sold or retired.')->schema([
            Select::make('decision')->options(['reuse'=>'Reuse','repair'=>'Repair','sell'=>'Sell','retire'=>'Retire'])->required(),Textarea::make('reason')->label('Reason & next steps')->rows(3)->required()->maxLength(2000),
        ])->action(function(array $data){$this->operations()->decide(auth()->user(),$this->currentItem(),$data);$this->done('Decision recorded');});
    }
    public function shareAction(): Action {
        return Action::make('share')->label('Give access')->icon('heroicon-o-user-plus')->visible(fn()=>$this->operations()->canShare(auth()->user(),$this->currentItem()))
            ->modalDescription('Give an existing Luxe Vault user access to this item only. Costs and revenue stay private. No invitation email is sent; the record appears in their Shared records.')->schema([
                TextInput::make('email')->label('Account email')->email()->required()->maxLength(255),TextInput::make('organization')->label('Organization')->required()->maxLength(120),
                Select::make('role')->label('Role on this item')->options(ItemOperations::ROLES)->required(),Select::make('access')->options(['view'=>'View only','contribute'=>'Add updates & evidence'])->default('view')->required(),DatePicker::make('expires_at')->label('Access expires (optional)')->minDate(now()->addDay()),
            ])->action(function(array $data){$this->operations()->grant(auth()->user(),$this->currentItem(),$data);$this->done('Item access updated');});
    }
    public function revokeAction(): Action {
        return Action::make('revoke')->label('Revoke access')->color('danger')->requiresConfirmation()->modalDescription('This person will lose access to this item and its evidence. Pending handoffs to them will be cancelled.')->visible(fn()=>$this->operations()->canShare(auth()->user(),$this->currentItem()))
            ->action(function(array $arguments){$this->operations()->revoke(auth()->user(),$this->currentItem(),(int)($arguments['shareId']??0));$this->done('Access revoked');});
    }
    public function handoffAction(): Action {
        return Action::make('handoff')->label('Request handoff')->icon('heroicon-o-arrow-right-circle')
            ->visible(fn()=>$this->operations()->canContribute(auth()->user(),$i=$this->currentItem()) && ($this->operations()->internal(auth()->user(),$i)||$i->responsible_user_id===auth()->id()))
            ->modalDescription('Responsibility changes only after the recipient accepts. Warehouse placement stays unchanged until your team moves the item.')->schema([
                Select::make('to_user_id')->label('Pass responsibility to')->options(fn()=>$this->operations()->participants($this->currentItem())->except(auth()->id())->pluck('name','id'))->searchable()->required(),DatePicker::make('due_on')->label('Due date (optional)')->minDate(today()),Textarea::make('instructions')->rows(3)->required()->maxLength(2000),
            ])->action(function(array $data){$this->operations()->requestHandoff(auth()->user(),$this->currentItem(),$data);$this->done('Handoff requested');});
    }
    public function respondAction(): Action {
        return Action::make('respond')->label('Respond to handoff')->schema([
            Select::make('response')->options(['accepted'=>'Accept responsibility','declined'=>'Decline'])->required(),Textarea::make('note')->label('Response / receiving notes')->rows(2)->maxLength(2000),
        ])->action(function(array $data,array $arguments){$this->operations()->respondHandoff(auth()->user(),$this->currentItem(),(int)($arguments['handoffId']??0),$data['response'],$data['note']??null);$this->done('Handoff updated');});
    }
    public function cancelHandoffAction(): Action {
        return Action::make('cancelHandoff')->label('Cancel handoff')->requiresConfirmation()->action(function(array $arguments){$this->operations()->respondHandoff(auth()->user(),$this->currentItem(),(int)($arguments['handoffId']??0),'cancelled');$this->done('Handoff cancelled');});
    }
}
