<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements CanResetPasswordContract, JWTSubject, MustVerifyEmail
{
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'email',
        'npwrd',
        'password',
        'master_opd_id',
        'master_lokasi_id',
        'master_role_id',
        'no_telp',
        'nama_instansi',
        'profile_pict',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function masterOpd()
    {
        return $this->belongsTo(MasterOpd::class, 'master_opd_id');
    }
    public function masterLokasi()
    {
        return $this->belongsTo(MasterLokasiObjek::class, 'master_lokasi_id');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'subject_id');
    }

    public function user()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function masterRole()
    {
        return $this->belongsTo(MasterRole::class, 'master_role_id');
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailApi);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordApi($token));
    }
}
