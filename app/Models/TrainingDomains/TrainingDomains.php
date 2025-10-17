<?php
namespace App\Models\TrainingDomains;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TrainingDomains extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "training_domains";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}