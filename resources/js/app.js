import { ScanConfidence } from './scan-confidence';
const register = () => {
 if (!window.Alpine || window.__vaultScannerRegistered) return;
 window.__vaultScannerRegistered = true;
 window.Alpine.data('vaultScanner', () => ({
  running:false, starting:false, message:'Scan a barcode or QR label to begin.', controls:null, reader:null, cancelled:false,
  progress:0, torchAvailable:false, torchOn:false, zoomAvailable:false, zoom:1, zoomMin:1, zoomMax:3, track:null,
  async start() {
   if(this.running || this.starting) return;
   if(!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { this.message='Camera is unavailable here. Use HTTPS or enter the code manually.'; return; }
   this.starting=true; this.cancelled=false; this.progress=0; this.confidence=new ScanConfidence(); this.message='Allow camera access, then aim at a label.';
   try {
    const { BrowserMultiFormatReader } = await import('@zxing/browser');
    const { DecodeHintType, BarcodeFormat } = await import('@zxing/library');
    const hints=new Map([[DecodeHintType.POSSIBLE_FORMATS,[BarcodeFormat.CODE_128,BarcodeFormat.CODE_39,BarcodeFormat.EAN_13,BarcodeFormat.EAN_8,BarcodeFormat.UPC_A,BarcodeFormat.UPC_E,BarcodeFormat.QR_CODE,BarcodeFormat.DATA_MATRIX]]]);
    this.reader=new BrowserMultiFormatReader(hints,{delayBetweenScanAttempts:120,delayBetweenScanSuccess:160});
    const controls=await this.reader.decodeFromConstraints({video:{facingMode:{ideal:'environment'},width:{ideal:1920},height:{ideal:1080}},audio:false},this.$refs.video,(result,error,controls)=>{
     if(result && !this.cancelled) {
      const code=result.getText(); const confirmed=this.confidence.accept(code); this.progress=this.confidence.count;
      this.message=confirmed?'Code confirmed. Checking your vault…':'Hold steady · confirming '+this.progress+' of 3 reads';
      if(!confirmed) return;
      this.cancelled=true; controls.stop(); this.running=false; this.torchOn=false;
      this.$wire.scanLookup(code).then(() => { this.message='Code confirmed. Review the result below.'; navigator.vibrate?.(60); }).catch(() => { this.message='Could not look up the code. Enter it below and try again.'; });
     }
    });
    this.controls=controls;
    if(this.cancelled) controls.stop(); else {
     this.running=true; this.message='Keep the whole barcode visible. We confirm three matching reads.';
     this.track=this.$refs.video.srcObject?.getVideoTracks()[0];
     const caps=this.track?.getCapabilities?.()||{};
     this.torchAvailable=!!caps.torch; this.zoomAvailable=!!caps.zoom;
     if(caps.zoom){this.zoomMin=caps.zoom.min;this.zoomMax=Math.min(caps.zoom.max,3);this.zoom=this.track.getSettings().zoom||caps.zoom.min;}
     if(caps.focusMode?.includes('continuous')) this.track.applyConstraints({advanced:[{focusMode:'continuous'}]}).catch(()=>{});
    }
   } catch(error) { this.message=error.name==='NotAllowedError'?'Camera permission was declined. You can enter the code manually.':'Could not open the camera. Check permissions or enter a code manually.'; }
   finally { this.starting=false; }
  },
  stop(){ this.cancelled=true; this.controls?.stop(); this.controls=null; this.$refs.video?.srcObject?.getTracks().forEach(t=>t.stop()); this.running=false; this.torchOn=false;this.progress=0; },
  async toggleTorch(){try{await this.track.applyConstraints({advanced:[{torch:!this.torchOn}]});this.torchOn=!this.torchOn;}catch{this.message='Light is unavailable on this camera.';}},
  async setZoom(){try{await this.track.applyConstraints({advanced:[{zoom:Number(this.zoom)}]});}catch{this.message='Zoom is unavailable on this camera.';}},
  init(){ this._onHide=()=>{if(document.hidden)this.stop()};document.addEventListener('visibilitychange',this._onHide); this._observer=new IntersectionObserver(entries=>{if(!entries[0].isIntersecting && (this.running||this.starting))this.stop()});this._observer.observe(this.$el); },
  destroy(){ this.stop();this._observer?.disconnect();document.removeEventListener('visibilitychange',this._onHide); }
 }));
};
document.addEventListener('alpine:init',register);
register();
