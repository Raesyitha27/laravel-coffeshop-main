<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kreait\Firebase\Factory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'total_price', 'status', 'address'];

    protected static function booted()
    {
        static::updated(function ($order) {
            // Sinkronisasi otomatis ke Firebase saat status di MySQL berubah
            if ($order->wasChanged('status')) {
                try {
                    $factory = (new Factory)
                        ->withServiceAccount(storage_path('app/service-account.json'))
                        ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

                    $database = $factory->createDatabase();
                    $database->getReference('Orders/' . $order->id)
                             ->update(['status' => $order->status]);
                } catch (\Exception $e) {
                    \Log::error("Firebase Sync Error: " . $e->getMessage());
                }
            }
        });
    }

    public function items() {
        return $this->hasMany(OrderItem::class);
    }
}