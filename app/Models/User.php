<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 'client' | 'livreur' | 'admin'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Laravel 11 : le cast "hashed" hache automatiquement le mot de passe
     * à l'écriture. Ne jamais appeler Hash::make() en plus dans le
     * contrôleur, sinon le mot de passe serait haché deux fois.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }
}
