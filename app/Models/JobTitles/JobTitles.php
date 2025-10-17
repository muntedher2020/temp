<?php
namespace App\Models\JobTitles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class JobTitles extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "job_titles";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}