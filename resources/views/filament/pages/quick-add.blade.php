<x-filament-panels::page><div class="lv-quick-intro"><span class="lv-pill">QUICK ADD</span><p>Three small steps. A place for every piece.</p></div>
@if(!\App\Models\Warehouse::visibleTo(auth()->user())->exists())<div class="lv-card"><h2>First, give your items a home.</h2><p>Create a warehouse before adding your first piece.</p><a class="lv-button" href="/admin/warehouses">Set up a warehouse →</a></div>@else
<form wire:submit="save">{{ $this->form }}</form>
@if($matchedItemId)<a class="lv-button lv-button-light" href="{{ route('items.label',$matchedItemId) }}" target="_blank">View matching item label {{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</a>@endif
@endif</x-filament-panels::page>