<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemCategory extends Model {
    protected $fillable = ['owner_id', 'name', 'normalized_name'];
}
