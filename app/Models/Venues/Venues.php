<?php
namespace App\Models\Venues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Venues extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "venues";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}