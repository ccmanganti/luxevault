<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemShare extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['expires_at'=>'datetime','revoked_at'=>'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function scopeActive($q) { return $q->whereNull('revoked_at')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now())); }
}
