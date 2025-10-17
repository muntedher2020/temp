<?php
namespace App\Models\TrainingInstitutions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TrainingInstitutions extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "training_institutions";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}