<x-filament-panels::page>
<div class="lv-browser">
    <aside class="lv-location-tree" x-data="{ expanded: true }">
        <button class="lv-tree-heading" type="button" @click="expanded = !expanded" :aria-expanded="expanded"><strong>Explore locations</strong><span x-text="expanded ? '−' : '+'"></span></button>
        <nav aria-label="Inventory locations" x-show="expanded" x-collapse>
            <button type="button" wire:click="chooseWarehouse(null)" @class(['lv-tree-row','is-selected'=>!$warehouseId])>All items</button>
            @foreach($warehouses as $warehouse)
                <button type="button" wire:key="warehouse-{{ $warehouse->id }}" wire:click="chooseWarehouse({{ $warehouse->id }})" @class(['lv-tree-row','is-selected'=>$warehouseId===$warehouse->id])><span>▸ {{ $warehouse->name }}</span><small>{{ $warehouse->items_count }}</small></button>
                @if($warehouseId===$warehouse->id)
                    <div class="lv-tree-children" wire:key="projects-{{ $warehouse->id }}">
                    @forelse($projects as $project)
                        <button type="button" wire:click="chooseProject({{ $project->id }})" @class(['lv-tree-row','is-selected'=>$projectId===$project->id])>▸ {{ $project->name }}</button>
                        @if($projectId===$project->id)
                            <div class="lv-tree-children" wire:key="groups-{{ $project->id }}">
                            @forelse($groups as $group)
                                <button type="button" wire:click="chooseGroup({{ $group->id }})" @class(['lv-tree-row','is-selected'=>$groupId===$group->id])><span>{{ $group->name }}</span><small>{{ $group->items_count }}</small></button>
                            @empty<p class="lv-tree-empty">No rooms or groups yet.</p>@endforelse
                            </div>
                        @endif
                    @empty<p class="lv-tree-empty">No projects yet.</p>@endforelse
                    </div>
                @endif
            @endforeach
        </nav>
        <p class="lv-tree-help">Set up locations in Resource management. Browse and edit their items here.</p>
    </aside>
    <div class="lv-browser-results">
        <nav class="lv-location-breadcrumb" aria-label="Current inventory location">
            <button wire:click="chooseWarehouse(null)" type="button">All items</button>
            @if($selectedWarehouse)<span>/</span><button wire:click="chooseWarehouse({{ $selectedWarehouse->id }})" type="button">{{ $selectedWarehouse->name }}</button>@endif
            @if($selectedProject)<span>/</span><button wire:click="chooseProject({{ $selectedProject->id }})" type="button">{{ $selectedProject->name }}</button>@endif
            @if($selectedGroup)<span>/</span><strong>{{ $selectedGroup->name }}</strong>@endif
        </nav>
        <div wire:loading.delay class="lv-browser-loading" role="status">Updating items…</div>
        {{ $this->table }}
    </div>
</div>
</x-filament-panels::page>
