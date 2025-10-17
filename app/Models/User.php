<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens,
        HasFactory,
        HasProfilePhoto,
        Notifiable,
        TwoFactorAuthenticatable,
        HasRoles,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plan',
        'status',
        'last_seen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'account_status',
        'connection_status',
    ];

    /* ------------------------ العلاقات ------------------------ */
    public function onlineSessions()
    {
        return $this->hasMany(OnlineSession::class);
    }

    /* ------------------------ دوال حالة الحساب ------------------------ */
    public function getAccountStatusAttribute()
    {
        return [
            'text' => $this->status ? 'مفعل' : 'غير مفعل',
            'class' => $this->status ? 'text-success' : 'text-danger'
        ];
    }

    /* ------------------------ دوال حالة الاتصال ------------------------ */
    public function isOnline()
    {
        return $this->onlineSessions()
            ->where('last_activity', '>=', now()->subMinutes(5))
            ->exists();
    }

    public function getConnectionStatusAttribute()
    {
        if (!$this->status) {
            return [
                'text' => '',
                'class' => ''
            ];
        }

        return $this->isOnline() ? [
            'text' => 'متصل',
            'class' => 'text-success'
        ] : [
            'text' => 'غير متصل',
            'class' => 'text-danger'
        ];
    }

    /* ------------------------ دوال مساعدة ------------------------ */
    public function getLastActivityTextAttribute()
    {
        return $this->last_seen
            ? $this->last_seen->diffForHumans()
            : 'لم يظهر أبداً';
    }
}
