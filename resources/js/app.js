const register = () => {
 if (!window.Alpine || window.__vaultScannerRegistered) return;
 window.__vaultScannerRegistered = true;
 window.Alpine.data('vaultScanner', () => ({
  running:false, starting:false, message:'Scan a barcode or QR label to begin.', controls:null, reader:null, cancelled:false,
  async start() {
   if(this.running || this.starting) return;
   if(!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) { this.message='Camera is unavailable here. Use HTTPS or enter the code manually.'; return; }
   this.starting=true; this.cancelled=false; this.message='Allow camera access, then aim at a label.';
   try {
    const { BrowserMultiFormatReader } = await import('@zxing/browser');
    this.reader=new BrowserMultiFormatReader();
    const controls=await this.reader.decodeFromConstraints({video:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720}},audio:false},this.$refs.video,(result,error,controls)=>{
     if(result && !this.cancelled) {
      const code=result.getText(); this.cancelled=true; controls.stop(); this.running=false;
      this.message='Code captured. Checking your vault…';
      this.$wire.scanLookup(code).then(() => { this.message='Code captured. Review the code below, then continue.'; }).catch(() => { this.message='Could not look up the code. Please enter it below and try again.'; });
     }
    });
    this.controls=controls;
    if(this.cancelled) controls.stop(); else { this.running=true; this.message='Keep the label steady inside the frame. Scanning automatically…'; }
   } catch(error) { this.message=error.name==='NotAllowedError'?'Camera permission was declined. You can enter the code manually.':'Could not open the camera. Check permissions or enter a code manually.'; }
   finally { this.starting=false; }
  },
  stop(){ this.cancelled=true; this.controls?.stop(); this.controls=null; this.$refs.video?.srcObject?.getTracks().forEach(t=>t.stop()); this.running=false; },
  init(){ this._onHide=()=>{if(document.hidden)this.stop()};document.addEventListener('visibilitychange',this._onHide); this._observer=new IntersectionObserver(entries=>{if(!entries[0].isIntersecting && (this.running||this.starting))this.stop()});this._observer.observe(this.$el); },
  destroy(){ this.stop();this._observer?.disconnect();document.removeEventListener('visibilitychange',this._onHide); }
 }));
};
document.addEventListener('alpine:init',register);
register();
