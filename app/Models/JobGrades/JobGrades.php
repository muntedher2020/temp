<?php
namespace App\Models\JobGrades;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class JobGrades extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "job_grades";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}