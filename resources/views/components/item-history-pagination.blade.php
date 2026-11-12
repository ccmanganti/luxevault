@if($pager->hasPages())<nav class="lv-op-actions" aria-label="History pages">
@if(!$pager->onFirstPage())<button type="button" class="lv-button lv-button-light" wire:click="previousPage('{{ $pageName }}')">← Newer</button>@endif
@if($pager->hasMorePages())<button type="button" class="lv-button lv-button-light" wire:click="nextPage('{{ $pageName }}')">Older →</button>@endif
</nav>@endif
