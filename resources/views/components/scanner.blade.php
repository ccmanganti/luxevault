<div x-data="vaultScanner" class="lv-scanner" wire:ignore>
 <div class="lv-camera"><video x-ref="video" autoplay muted playsinline aria-label="Barcode camera preview"></video><div class="lv-camera-reticle"></div><span x-show="!running">Point. Scan. Place.</span></div>
 <div class="lv-camera-actions"><button type="button" class="lv-button" @click="start()" :disabled="running || starting" x-text="starting ? 'Opening camera…' : 'Open camera'"></button><button type="button" class="lv-button lv-button-light" @click="stop()" x-show="running">Stop camera</button></div>
 <p x-text="message" role="status" class="lv-muted"></p><small>Camera access needs HTTPS and permission. You can always type a code below.</small>
</div>