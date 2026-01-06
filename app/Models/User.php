<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // <-- Wajib di-import

class User extends Authenticatable implements JWTSubject // <-- Implementasi JWT
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // --- Implementasi JWTSubject ---

    // Menentukan ID yang dimasukkan ke payload (sub claim)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Untuk klaim kustom tambahan (opsional)
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function wishlists()
    {
    return $this->hasMany(Wishlist::class);
    }
}

?>