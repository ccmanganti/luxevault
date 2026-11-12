<div x-data="vaultScanner" class="lv-scanner" wire:ignore @vault-scan-stop.window="stop()" @vault-scan-reset.window="stop(); message = 'Scan a barcode or QR label to begin.'">
 <div class="lv-camera-state"><span><i :class="running ? 'is-live' : ''" aria-hidden="true"></i><span x-text="running ? 'Camera live' : starting ? 'Connecting…' : 'Camera ready'">Camera ready</span></span><span x-show="running" x-cloak>Hold steady</span></div>
 <div class="lv-camera">
  <video x-ref="video" autoplay muted playsinline aria-label="Barcode camera preview"></video>
  <div class="lv-camera-reticle" aria-hidden="true"></div>
  <button type="button" class="lv-camera-launch" @click="start()" x-show="!running" :disabled="starting" aria-label="Open camera to scan a barcode">
   <span class="lv-camera-play">{{ svg('heroicon-s-play') }}</span><strong x-text="starting ? 'Opening camera…' : 'Open camera'">Open camera</strong><small>Scan a barcode or QR label</small>
  </button>
 </div>
 <div class="lv-scanner-feedback"><span aria-hidden="true">{{ svg('heroicon-o-viewfinder-circle') }}</span><p x-text="message" role="status" aria-live="polite">Scan a barcode or QR label to begin.</p></div>
 <div class="lv-camera-controls" x-show="running || starting" x-cloak><button type="button" class="lv-button lv-button-light" @click="stop()" x-text="starting ? 'Cancel' : 'Stop camera'"></button><button type="button" class="lv-button lv-button-light" x-show="running && torchAvailable" @click="toggleTorch()" :aria-pressed="torchOn" x-text="torchOn ? 'Light off' : 'Light on'"></button><label x-show="running && zoomAvailable">Zoom <input type="range" :min="zoomMin" :max="zoomMax" step="0.1" x-model="zoom" @input.debounce.150ms="setZoom()" aria-label="Camera zoom"></label></div>
 <details class="lv-scan-help"><summary>Tips for a clearer scan</summary><p>Keep the whole barcode visible, with space at both ends. Avoid glare and hold steady for three matching reads. Scan the bars or QR code; use Type code for printed numbers.</p></details>
</div>
