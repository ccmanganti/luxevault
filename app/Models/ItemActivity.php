<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemActivity extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['attachments'=>'array']; }
    public function user() { return $this->belongsTo(User::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
