<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemEntry extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['amount'=>'decimal:2','occurred_on'=>'date','voided_at'=>'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
}
