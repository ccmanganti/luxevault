<x-filament-panels::page>
 <div class="lv-record-top"><a href="{{ $internal ? \App\Filament\Pages\BrowseInventory::getUrl() : \App\Filament\Pages\SharedRecords::getUrl() }}" class="lv-record-link">← {{ $internal ? 'Browse inventory' : 'Shared records' }}</a><span class="lv-pill">{{ $internal ? 'YOUR VAULT' : 'SHARED WITH YOU' }}</span></div>
 <section class="lv-op-card">
  <div class="lv-record-hero"><img src="{{ $item->cover_photo_url }}" alt="{{ $item->name }}"><div><h1>{{ $item->name }}</h1><p>{{ $item->sku }} · {{ $item->category }}</p><span class="lv-item-badge">{{ \App\Services\ItemOperations::STATUSES[$item->status] ?? $item->status }}</span></div></div>
  <p class="lv-record-location">{{ $item->warehouse->name }}@if($item->inventoryGroup) · {{ $item->inventoryGroup->project->name }} / {{ $item->inventoryGroup->name }}@endif @if($item->location) · {{ $item->location }}@endif</p>
  <div class="lv-op-actions">{{ $this->statusAction }} {{ $this->moveAction }} {{ $this->stockAction }} {{ $this->noteAction }}</div>
 </section>
 <nav class="lv-record-tabs" aria-label="Item sections">
  @foreach(['overview'=>'Overview','investment'=>'Investment','history'=>'Sharing & handoffs'] as $key=>$label)
   @if($key!=='investment'||$internal)<button type="button" wire:click="selectTab('{{ $key }}')" class="{{ $tab===$key?'is-active':'' }}" aria-current="{{ $tab===$key?'page':'false' }}">{{ $label }}</button>@endif
  @endforeach
 </nav>
 <div wire:loading wire:target="selectTab" role="status" class="lv-record-subtle">Opening section…</div>
 <div class="lv-record-pane" wire:key="record-tab-{{ $tab }}">
 @if($tab==='overview')
  <div class="lv-record-feature-links">
   @if($internal)<button type="button" wire:click="selectTab('investment')"><span class="lv-feature-icon">{{ svg('heroicon-o-chart-bar') }}</span><span><strong>Investment & decisions</strong><small>Log costs, usage and revenue</small></span>{{ svg('heroicon-o-arrow-right','lv-feature-arrow') }}</button>@endif
   <button type="button" wire:click="selectTab('history')"><span class="lv-feature-icon">{{ svg('heroicon-o-user-group') }}</span><span><strong>Sharing & handoffs</strong><small>People, evidence and responsibility</small></span>{{ svg('heroicon-o-arrow-right','lv-feature-arrow') }}</button>
  </div>
  <div class="lv-op-grid"><section class="lv-op-card"><h2>At a glance</h2><dl class="lv-key-values"><div><dt>Quantity</dt><dd>{{ $item->quantity }}</dd></div><div><dt>Condition</dt><dd>{{ ucfirst($item->condition) }}</dd></div><div><dt>Responsible</dt><dd>{{ $responsible?->name ?? 'Not assigned' }}</dd></div><div><dt>Barcode</dt><dd>{{ $item->barcode ?: 'Not set' }}</dd></div>@if($internal)<div><dt>Unit value</dt><dd>USD ${{ number_format((float)$item->unit_price,2) }}</dd></div>@endif</dl></section>
  <section class="lv-op-card"><h2>Next handoff</h2>
   @if($pending)<span class="lv-item-badge">Awaiting acceptance</span><p><strong>{{ $pending->sender->name }}</strong> → <strong>{{ $pending->recipient->name }}</strong></p><p class="lv-record-text">{{ $pending->instructions }}</p>@if($pending->due_on)<p class="lv-record-subtle">Due {{ $pending->due_on->format('M j, Y') }}</p>@endif
    <div class="lv-op-actions">@if($pending->to_user_id===auth()->id())<x-filament::button wire:click="mountAction('respond', { handoffId: {{ $pending->id }} })">Respond</x-filament::button>@endif
    @if($pending->from_user_id===auth()->id()||$canShare)<x-filament::button color="gray" wire:click="mountAction('cancelHandoff', { handoffId: {{ $pending->id }} })">Cancel handoff</x-filament::button>@endif</div>
   @else<p class="lv-record-subtle">Pass responsibility with clear instructions. The recipient accepts before responsibility changes.</p>{{ $this->handoffAction }}@endif
  </section></div>
  @if($internal && $item->notes)<section class="lv-op-card"><h2>Internal notes</h2><p class="lv-record-text">{{ $item->notes }}</p></section>@endif
  @if(count($item->photos??[])>1)<section class="lv-op-card"><h2>Photos</h2><div class="lv-record-gallery">@foreach($item->photos as $index=>$photo)<a href="{{ route('inventory.photo',['item',$item->id,$index]) }}" target="_blank" rel="noopener"><img src="{{ route('inventory.photo',['item',$item->id,$index]) }}" alt="{{ $item->name }} photo {{ $index+1 }}" loading="lazy"></a>@endforeach</div></section>@endif
 @elseif($tab==='investment' && $investment)
  <div class="lv-op-heading"><p class="lv-record-subtle">Private to your account · USD · Totals for this item record</p><div class="lv-op-actions">{{ $this->entryAction }} {{ $this->investmentAction }}</div></div>
  <div class="lv-money-grid">
   @foreach(['invested'=>'Total invested','revenue'=>'Revenue recorded','net'=>'Net recorded'] as $key=>$label)<section class="lv-op-card lv-money"><span>{{ $label }}</span><strong>${{ number_format($investment[$key],2) }}</strong></section>@endforeach
   <section class="lv-op-card lv-money"><span>Uses / days in use</span><strong>{{ $investment['uses'] }} <small>/ {{ $investment['days'] }}</small></strong></section>
  </div>
  <div class="lv-op-grid"><section class="lv-op-card lv-decision-card"><span class="lv-pill">DECISION SUPPORT</span><h2>{{ $investment['advice'] }}</h2><p>{{ $investment['reason'] }}</p><p class="lv-record-subtle">Based on recorded history and your estimates. You make the decision.</p>{{ $this->decisionAction }}</section>
   <section class="lv-op-card"><h2>What went into it</h2><dl class="lv-key-values">@foreach(['acquisition'=>'Acquisition','storage'=>'Storage','repairs'=>'Repairs','other'=>'Other costs'] as $key=>$label)<div><dt>{{ $label }}</dt><dd>${{ number_format($investment[$key],2) }}</dd></div>@endforeach<div><dt>Recorded cost per use</dt><dd>{{ $investment['cost_per_use']!==null?'$'.number_format($investment['cost_per_use'],2):'Log a use first' }}</dd></div><div><dt>Last used</dt><dd>{{ $investment['last_used'] ?? 'Not recorded' }}</dd></div></dl></section>
  </div>
  <section class="lv-op-card"><h2>Recent entries</h2><p class="lv-record-subtle">All entries, 20 at a time. Storage and revenue are included when you log them; no recurring charges are assumed.</p>
   @forelse($entries as $entry)<div class="lv-entry-row {{ $entry->voided_at?'is-void':'' }}"><div><strong>{{ ucfirst(str_replace('_',' ',$entry->kind)) }}</strong><small>{{ $entry->occurred_on->format('M j, Y') }} · {{ $entry->user?->name ?? 'Former user' }}</small>@if($entry->notes)<p>{{ $entry->notes }}</p>@endif @if($entry->voided_at)<small>Excluded: {{ $entry->void_reason }}</small>@endif</div><div><strong>{{ $entry->kind==='usage'?$entry->uses.' uses · '.$entry->days.' days':'$'.number_format((float)$entry->amount,2) }}</strong>@if(!$entry->voided_at && auth()->user()->can('update',$item))<button type="button" class="lv-text-button" wire:click="mountAction('voidEntry', { entryId: {{ $entry->id }} })">Correct</button>@endif</div></div>@empty<p class="lv-record-subtle">Log the first use, cost or revenue to start building this item's history.</p>@endforelse
   @include('components.item-history-pagination',['pager'=>$entries,'pageName'=>'entryPage'])
  </section>
 @elseif($tab==='history')
  <div class="lv-op-grid"><section class="lv-op-card"><div class="lv-op-heading"><h2>Updates & decisions</h2>{{ $this->noteAction }}</div><p class="lv-record-subtle">Shared updates are visible to approved collaborators. Financial entries and access changes stay internal.</p>
   @forelse($activities as $activity)<article class="lv-timeline-entry"><div><strong>{{ $activity->user?->name ?? 'Former user' }}</strong><small>{{ $activity->created_at->format('M j, Y · H:i') }} UTC · {{ ucfirst($activity->kind) }}{{ $activity->visibility==='internal'?' · Internal':'' }}</small></div><p class="lv-record-text">{{ $activity->body }}</p>@if($activity->attachments)<div class="lv-evidence-links">@foreach($activity->attachments as $index=>$path)<a href="{{ route('item.evidence',[$activity->id,$index]) }}">{{ strtoupper(pathinfo($path,PATHINFO_EXTENSION)) }} evidence {{ $index+1 }} ↓</a>@endforeach</div>@endif</article>@empty<p class="lv-record-subtle">Add an update or receiving photo to begin the shared history.</p>@endforelse
   @include('components.item-history-pagination',['pager'=>$activities,'pageName'=>'activityPage'])
  </section><section class="lv-op-card"><h2>People & responsibility</h2><p>Responsible: <strong>{{ $responsible?->name ?? 'Not assigned' }}</strong></p>{{ $this->handoffAction }}
   @if($canShare)<div class="lv-sharing-controls"><div class="lv-op-heading"><h3>External access</h3>{{ $this->shareAction }}</div><p class="lv-record-subtle">Specific people, this item only. No financial access.</p>
    @forelse($shares as $share)<div class="lv-share-row"><strong>{{ $share->user->name }}</strong><small>{{ $share->organization }} · {{ \App\Services\ItemOperations::ROLES[$share->role] ?? $share->role }} · {{ $share->access==='view'?'View only':'Contributor' }}</small>@if($share->revoked_at)<small>Revoked</small>@elseif($share->expires_at?->isPast())<small>Expired</small>@else @if($share->expires_at)<small>Expires {{ $share->expires_at->format('M j, Y') }}</small>@endif<button type="button" class="lv-text-button" wire:click="mountAction('revoke', { shareId: {{ $share->id }} })">Revoke</button>@endif</div>@empty<p class="lv-record-subtle">No external collaborators yet.</p>@endforelse
   </div>@else<p class="lv-record-subtle">The item owner manages access. Contact them when another person needs this record.</p>@endif
  </section></div>
 @endif
 </div>
</x-filament-panels::page>
