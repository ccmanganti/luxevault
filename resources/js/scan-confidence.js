export class ScanConfidence {
    constructor(required = 3, windowMs = 1800) { this.required = required; this.windowMs = windowMs; this.reset(); }
    reset() { this.value = null; this.count = 0; this.started = 0; }
    accept(value, now = Date.now()) {
        if (typeof value !== 'string' || !value.trim() || value.length > 120) { this.reset(); return false; }
        if (value !== this.value || now - this.started > this.windowMs) { this.value = value; this.count = 1; this.started = now; }
        else this.count++;
        return this.count >= this.required;
    }
}
