<?php
namespace App\Models\Departments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Departments extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "departments";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}