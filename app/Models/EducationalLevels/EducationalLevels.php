<?php
namespace App\Models\EducationalLevels;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EducationalLevels extends Model
{
     use HasFactory;
    protected $guarded = [];
    protected $table = "educational_levels";

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}