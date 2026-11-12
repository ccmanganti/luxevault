<div x-data="vaultScanner" class="lv-scanner" wire:ignore>
 <div class="lv-camera-state"><span>SCAN YOUR PIECE</span><span x-text="running ? 'Camera live' : starting ? 'Connecting…' : 'Ready when you are'"></span></div>
 <div class="lv-camera">
  <video x-ref="video" autoplay muted playsinline aria-label="Barcode camera preview"></video>
  <div class="lv-camera-reticle" aria-hidden="true"></div>
  <button type="button" class="lv-camera-launch" @click="start()" x-show="!running" :disabled="starting" aria-label="Open camera to scan a barcode">
   <span class="lv-camera-play"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span>
   <strong x-text="starting ? 'Opening your camera…' : 'Tap to start scanning'"></strong>
  </button>
 </div>
 <p class="lv-scan-hint">Keep the entire barcode in view, with space at both ends. Avoid glare. Scan the bars or QR code, not the printed numbers.</p>
 <div class="lv-camera-actions"><button type="button" class="lv-button" @click="running || starting ? stop() : start()" x-text="starting ? 'Cancel camera' : running ? 'Stop camera' : 'Open camera'"></button></div>
 <div class="lv-scan-tools" x-show="running" x-cloak><button type="button" class="lv-button lv-button-light" x-show="torchAvailable" @click="toggleTorch()" x-text="torchOn?'Light off':'Light on'"></button><label x-show="zoomAvailable">Zoom <input type="range" :min="zoomMin" :max="zoomMax" step="0.1" x-model="zoom" @input.debounce.150ms="setZoom()" aria-label="Camera zoom"></label></div>
 <p x-text="message" role="status" aria-live="polite" class="lv-muted"></p><small>Allow camera access when prompted, or enter a code below.</small>
</div>
