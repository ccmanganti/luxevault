<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemHandoff extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['due_on'=>'date','responded_at'=>'datetime']; }
    public function sender() { return $this->belongsTo(User::class,'from_user_id'); }
    public function recipient() { return $this->belongsTo(User::class,'to_user_id'); }
}
