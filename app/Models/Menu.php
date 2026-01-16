<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Wishlist;
use Kreait\Firebase\Factory;

class Menu extends Model
{
    use HasFactory;

    // Gabungkan semua field ke dalam satu array fillable
    protected $fillable = [
        'nama_menu', 
        'deskripsi', 
        'harga', 
        'kategori', 
        'picUrl', 
        'extra', 
        'quantity'
    ];

    /**
     * Relasi ke Wishlist
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Fungsi booted untuk sinkronisasi otomatis ke Firebase
     * dijalankan setiap kali data Menu disimpan (save/update).
     */
    protected static function booted()
    {
        static::saved(function ($menu) {
            try {
                // Pastikan file service-account.json ada di storage/app/
                $factory = (new Factory)
                    ->withServiceAccount(storage_path('app/service-account.json'))
                    ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

                $database = $factory->createDatabase();

                // Sinkronisasi ke node 'Popular' di Firebase
                $database->getReference('Popular/' . $menu->id)->set([
                    'title'       => $menu->nama_menu,
                    'price'       => (float) $menu->harga,
                    'description' => $menu->deskripsi,
                    'picUrl'      => $menu->picUrl,
                    'extra'       => $menu->extra,
                    'quantity'    => (int) $menu->quantity,
                    'rating'      => 4.5, // Nilai default
                ]);
            } catch (\Exception $e) {
                // Log error jika Firebase gagal agar server Laravel tidak crash
                \Log::error('Firebase Sync Error: ' . $e->getMessage());
            }
        });
    }
}