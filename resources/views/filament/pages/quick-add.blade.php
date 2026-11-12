<x-filament-panels::page>
 <div class="lv-scan-workspace" x-data="{ mode: 'camera', showInput: @js(!$lookedUp), revealResult() { this.showInput = false; this.$nextTick(() => { this.$refs.result?.focus({ preventScroll: true }); this.$refs.result?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }); }); } }"
      @vault-scan-result.window="revealResult()" @vault-scan-reset.window="mode = 'camera'; showInput = true; $nextTick(() => $refs.cameraTab?.focus())">
  @if($showCreate)
   <section class="lv-op-card lv-new-piece"><div class="lv-op-heading"><div><span class="lv-scan-eyebrow">NEW ITEM</span><h2>Add a piece to your vault</h2></div><button type="button" wire:click="cancelCreate" class="lv-button lv-button-light">← Back to scanner</button></div><form wire:submit="save" class="lv-quick-form">{{ $this->form }}</form></section>
  @else
   <div class="lv-scan-toolbar"><ol class="lv-scan-steps" aria-label="Scanning progress"><li @class(['is-current'=>!$lookedUp])><span>1</span>Find</li><li @class(['is-current'=>$lookedUp&&!$matched])><span>2</span>Confirm</li><li @class(['is-current'=>(bool)$matched])><span>3</span>Take action</li></ol><button type="button" class="lv-scan-add" wire:click="beginCreate">{{ svg('heroicon-o-plus') }} Add without scanning</button></div>
   <div class="lv-scan-flow" :class="showInput ? 'is-finding' : 'has-result'">
    <section class="lv-op-card lv-scan-input" x-show="showInput" x-transition.opacity.duration.150ms aria-label="Find an item">
     <div class="lv-scan-mode" role="tablist" aria-label="How to find an item">
      <button id="scan-camera-tab" x-ref="cameraTab" type="button" role="tab" aria-controls="scan-camera-panel" :aria-selected="mode === 'camera'" :tabindex="mode === 'camera' ? 0 : -1" :class="mode === 'camera' ? 'is-selected' : ''" @click="mode = 'camera'" @keydown.arrow-right.prevent="mode = 'manual'; $dispatch('vault-scan-stop'); $refs.manualTab.focus()">{{ svg('heroicon-o-camera') }} Camera</button>
      <button id="scan-manual-tab" x-ref="manualTab" type="button" role="tab" aria-controls="scan-manual-panel" :aria-selected="mode === 'manual'" :tabindex="mode === 'manual' ? 0 : -1" :class="mode === 'manual' ? 'is-selected' : ''" @click="mode = 'manual'; $dispatch('vault-scan-stop'); $nextTick(() => $refs.code.focus())" @keydown.arrow-left.prevent="mode = 'camera'; $refs.cameraTab.focus()">{{ svg('heroicon-o-pencil-square') }} Type code</button>
     </div>
     <div id="scan-camera-panel" role="tabpanel" aria-labelledby="scan-camera-tab" x-show="mode === 'camera'">@include('components.scanner')</div>
     <div id="scan-manual-panel" role="tabpanel" aria-labelledby="scan-manual-tab" x-show="mode === 'manual'" x-cloak>
      <div class="lv-manual-intro"><h2>Enter the item's code</h2><p>Type a SKU or barcode, or use a handheld scanner.</p></div>
      <form wire:submit="scanLookup" class="lv-code-search"><label for="lookup-code">Barcode, QR value or SKU</label><input id="lookup-code" x-ref="code" wire:model="lookupCode" maxlength="120" placeholder="For example, LV-ABC123" autocomplete="off" autocapitalize="off" spellcheck="false" aria-describedby="lookup-help"><p id="lookup-help">Keep any leading zeros. Press Enter to search.</p><button type="submit" class="lv-button" wire:loading.attr="disabled" wire:target="scanLookup"><span wire:loading.remove wire:target="scanLookup">Find item {{ svg('heroicon-o-arrow-right') }}</span><span wire:loading wire:target="scanLookup">Finding item…</span></button>@error('lookupCode')<p class="lv-error" role="alert">{{ $message }}</p>@enderror</form>
     </div>
     <div wire:loading wire:target="scanLookup" class="lv-scan-loading" role="status">Checking your vault…</div>
    </section>
    @if($lookedUp)
     <section class="lv-op-card lv-scan-result" x-ref="result" tabindex="-1" aria-label="Scan result" x-show="!showInput" x-transition.opacity.duration.150ms>
      <div class="lv-scan-result-top"><span class="lv-scan-eyebrow">{{ $matched ? 'ITEM FOUND' : (count($matchIds)>1 ? 'CHOOSE YOUR ITEM' : 'NO MATCH YET') }}</span><button type="button" class="lv-button lv-button-light" wire:click="resetScan">{{ svg('heroicon-o-qr-code') }} Scan next</button></div>
      @if($matched)
       <div class="lv-record-hero"><img src="{{ $matched->cover_photo_url }}" alt="{{ $matched->name }}"><div><h2>{{ $matched->name }}</h2><p>{{ $matched->sku }}</p><span class="lv-item-badge">{{ \App\Services\ItemOperations::STATUSES[$matched->status] ?? $matched->status }}</span></div></div>
       <p class="lv-record-location">{{ $matched->warehouse->name }}@if($matched->inventoryGroup) · {{ $matched->inventoryGroup->project->name }} / {{ $matched->inventoryGroup->name }}@endif</p>
       <p class="lv-record-subtle">{{ $matched->quantity }} in stock · {{ ucfirst($matched->condition) }} condition</p>
       <div class="lv-scan-actions"><h3>What would you like to do?</h3><div class="lv-op-actions">{{ $this->statusAction }} {{ $this->moveAction }} {{ $this->stockAction }}</div></div>
       <nav class="lv-scan-record-links" aria-label="More for this item"><a href="{{ \App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$matched->id]) }}">{{ svg('heroicon-o-cube') }} Item details</a><a href="{{ \App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$matched->id,'tab'=>'investment']) }}">{{ svg('heroicon-o-chart-bar') }} Investment</a><a href="{{ \App\Filament\Pages\ItemRecord::getUrl(['itemId'=>$matched->id,'tab'=>'history']) }}">{{ svg('heroicon-o-user-group') }} Share & handoff</a></nav>
       <details class="lv-scan-help"><summary>Another piece with the same barcode?</summary><p>Manufacturer barcodes can belong to multiple pieces. Add a separate record only when this is a different item.</p><button type="button" class="lv-button lv-button-light" wire:click="beginCreate">Add a separate piece</button></details>
      @elseif(count($matchIds)>1)
       <h2>Which piece is this?</h2><p class="lv-record-subtle">Several items use this barcode. Check the warehouse and SKU, then choose one.</p>
       <div class="lv-match-list">@foreach($matches->take(20) as $match)<button type="button" class="lv-match-row" wire:click="selectMatch({{ $match->id }})" wire:loading.attr="disabled"><span><strong>{{ $match->name }}</strong><small>{{ $match->warehouse->name }} · {{ $match->sku }}</small></span>{{ svg('heroicon-o-arrow-right') }}</button>@endforeach</div>
       @if(count($matchIds)>20)<p class="lv-record-subtle">More matches exist. Use the unique Luxe Vault QR label or SKU to narrow it down.</p>@endif
      @else
       <h2>No item found for this code</h2><p class="lv-code-value">{{ $lookupCode }}</p><p class="lv-record-subtle">Check the code or create a record if this is a new piece.</p><div class="lv-op-actions"><button type="button" class="lv-button" wire:click="beginCreate">Add this item</button><button type="button" class="lv-button lv-button-light" @click="showInput = true; mode = 'manual'; $nextTick(() => $refs.code.focus())">Edit code</button></div>
      @endif
     </section>
    @else
     <aside class="lv-scan-guide"><span class="lv-scan-eyebrow">AFTER YOU SCAN</span><h2>Find a piece.<br>Keep things moving.</h2><p>Your item and its quick actions appear here after a match.</p><ul><li>{{ svg('heroicon-o-check-circle') }} Update status or condition</li><li>{{ svg('heroicon-o-arrows-right-left') }} Move to a room or warehouse</li><li>{{ svg('heroicon-o-cube') }} Adjust the quantity</li></ul><a href="{{ \App\Filament\Pages\BrowseInventory::getUrl() }}">Browse items instead {{ svg('heroicon-o-arrow-right') }}</a></aside>
    @endif
   </div>
  @endif
 </div>
</x-filament-panels::page>
