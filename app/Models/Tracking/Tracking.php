<?php
namespace App\Models\Tracking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tracking extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = "trackings";
    protected $fillable = ['user_id', 'page_name', 'operation_type', 'operation_time', 'details'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
