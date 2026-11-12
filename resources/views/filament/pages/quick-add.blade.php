<x-filament-panels::page>
 <div class="lv-quick-intro"><span class="lv-pill">SCAN & ACT</span><p>Find a piece, update it, and keep moving.</p></div>
 <div class="lv-scan-layout">
  <section class="lv-op-card">@include('components.scanner')
   <form wire:submit="scanLookup" class="lv-code-search"><label for="lookup-code">Or enter a barcode, QR value or SKU</label><div><input id="lookup-code" wire:model="lookupCode" maxlength="120" placeholder="Type or use a handheld scanner" autocomplete="off" spellcheck="false"><button type="submit" class="lv-button" wire:loading.attr="disabled">Find item</button></div>@error('lookupCode')<p class="lv-error">{{ $message }}</p>@enderror</form>
  </section>
  <section class="lv-op-card lv-scan-result" aria-live="polite">
   <div wire:loading wire:target="scanLookup,selectMatch" role="status">Finding your item…</div>
   @if($matched)
    <span class="lv-pill">FOUND IN YOUR VAULT</span>
    <div class="lv-record-hero"><img src="{{ $matched->cover_photo_url }}" alt="{{ $matched->name }}"><div><h2>{{ $matched->name }}</h2><p>{{ $matched->sku }}</p><span class="lv-item-badge">{{ \App\Services\ItemOperations::STATUSES[$matched->status] ?? $matched->status }}</span></div></div>
    <p class="lv-record-location">{{ $matched->warehouse->name }}@if($matched->inventoryGroup) · {{ $matched->inventoryGroup->project->name }} / {{ $matched->inventoryGroup->name }}@endif</p>
    <p class="lv-record-subtle">{{ $matched->quantity }} in stock · {{ ucfirst($matched->condition) }} condition</p>
    <div class="lv-op-actions">{{ $this->statusAction }} {{ $this->moveAction }} {{ $this->stockAction }}</div>
    <a class="lv-record-link" href="{{ \App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$matched->id]) }}">Open item record, investment & history →</a>
    <button type="button" class="lv-text-button" wire:click="beginCreate">Add a separate piece with this code</button>
   @elseif(count($matchIds)>1)
    <h2>Which item did you scan?</h2><p class="lv-record-subtle">This code belongs to more than one item. Choose the right piece before taking action.</p>
    @foreach($matches->take(20) as $match)<button type="button" class="lv-match-row" wire:click="selectMatch({{ $match->id }})"><span><strong>{{ $match->name }}</strong><small>{{ $match->warehouse->name }} · {{ $match->sku }}</small></span><span>→</span></button>@endforeach
    @if(count($matchIds)>20)<p>More matches exist. Scan the unique Luxe Vault QR label or enter its SKU.</p>@endif
   @elseif($lookedUp)
    <span class="lv-pill">NEW PIECE?</span><h2>No matching item</h2><p class="lv-record-subtle">Check the code <strong>{{ $lookupCode }}</strong>, try another scan, or create a new item.</p><button type="button" class="lv-button" wire:click="beginCreate">Add this item</button>
   @else
    <span class="lv-pill">ONE SCAN, LESS TAPPING</span><h2>Your item appears here.</h2><p class="lv-record-subtle">Change its status, move it to a room or warehouse, or adjust stock without opening the full editor.</p><button type="button" class="lv-button lv-button-light" wire:click="beginCreate">Add without scanning</button>
   @endif
  </section>
 </div>
 @if($showCreate)<section class="lv-op-card lv-new-piece" wire:key="new-piece"><div class="lv-op-heading"><h2>Add a new piece</h2><button type="button" wire:click="cancelCreate" class="lv-text-button">Cancel</button></div><form wire:submit="save" class="lv-quick-form">{{ $this->form }}</form></section>@endif
</x-filament-panels::page>
