<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    // Menentukan tabel jika nama tabel Anda bukan 'carts' (opsional)
    // protected $table = 'carts';

    protected $fillable = [
        'user_id', 
        'menu_id', 
        'quantity'
    ];

    /**
     * Relasi ke Model User
     * Keranjang ini dimiliki oleh seorang User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Model Menu
     * Keranjang ini berisi sebuah Menu.
     * Dengan relasi ini, Anda bisa memanggil $cart->menu->name atau $cart->menu->price
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}