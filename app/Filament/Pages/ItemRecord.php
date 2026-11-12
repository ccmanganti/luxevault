<?php
namespace App\Filament\Pages;
use App\Models\Item;
use App\Support\ItemInvestment;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
class ItemRecord extends Page {
    use \App\Filament\Concerns\InteractsWithItemActions;
    use \Livewire\WithPagination;
    protected static bool $shouldRegisterNavigation=false;
    protected static ?string $title='Item record';
    protected string $view='filament.pages.item-record';
    #[Url] public ?int $itemId=null;
    #[Url] public string $tab='overview';
    public function mount(): void { $this->currentItem();abort_unless(in_array($this->tab,['overview','investment','history'],true),404); }
    protected function currentItem(): Item { return $this->cachedRecord($this->itemId??0); }
    public function selectTab(string $tab): void { abort_unless(in_array($tab,['overview','investment','history'],true),404);if($tab==='investment')abort_unless($this->operations()->internal(auth()->user(),$this->currentItem()),403);$this->tab=$tab;$this->resetPage('activityPage');$this->resetPage('entryPage'); }
    protected function getViewData(): array {
        $item=$this->currentItem();$internal=$this->operations()->internal(auth()->user(),$item);$canShare=$this->operations()->canShare(auth()->user(),$item);
        abort_unless(in_array($this->tab,['overview','investment','history'],true),404);
        abort_if($this->tab==='investment'&&!$internal,403);
        $activities=$item->activities()->when(!$internal,fn($q)=>$q->where('visibility','shared'))->with('user')->latest('id')->simplePaginate(20,['*'],'activityPage');
        return compact('item','internal','canShare','activities')+[
            'investment'=>$this->tab==='investment'&&$internal?ItemInvestment::summary($item):null,
            'entries'=>$this->tab==='investment'&&$internal?$item->entries()->with('user')->latest('occurred_on')->latest('id')->simplePaginate(20,['*'],'entryPage'):collect(),
            'shares'=>$canShare&&$this->tab==='history'?$item->shares()->with('user')->latest()->get():collect(),
            'pending'=>$item->handoffs()->where('status','pending')->with(['sender','recipient'])->first(),
            'responsible'=>$item->responsible && $this->operations()->canView($item->responsible,$item)?$item->responsible:null,
        ];
    }
}
