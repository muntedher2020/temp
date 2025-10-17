<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineSession extends Model
{
    protected $fillable = ['user_id', 'session_id', 'last_activity'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
