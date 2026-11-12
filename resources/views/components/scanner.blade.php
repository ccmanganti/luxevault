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
 <p class="lv-scan-hint">Hold the label inside the frame. Keep your phone steady.</p>
 <div class="lv-camera-actions"><button type="button" class="lv-button" @click="running || starting ? stop() : start()" x-text="starting ? 'Cancel camera' : running ? 'Stop camera' : 'Open camera'"></button></div>
 <p x-text="message" role="status" aria-live="polite" class="lv-muted"></p><small>Allow camera access when prompted, or enter a code below.</small>
</div>
