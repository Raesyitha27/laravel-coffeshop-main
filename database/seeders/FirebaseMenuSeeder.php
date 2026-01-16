<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use Kreait\Firebase\Factory;

class FirebaseMenuSeeder extends Seeder
{
    public function run(): void
{
    // 1. Izinkan input ID 0 ke database (Khusus MySQL)
    \DB::statement('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');

    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount(storage_path('app/service-account.json'))
        ->withDatabaseUri(env('FIREBASE_DATABASE_URL'));

    $database = $factory->createDatabase();
    $firebaseData = $database->getReference('Popular')->getValue();

    if ($firebaseData) {
        foreach ($firebaseData as $key => $item) {
            $urlGambar = null;
            if (isset($item['picUrl'])) {
                $urlGambar = is_array($item['picUrl']) ? reset($item['picUrl']) : $item['picUrl'];
            }

            // Gunakan updateOrInsert agar lebih stabil untuk ID 0
            \DB::table('menus')->updateOrInsert(
                ['id' => (int)$key], // Paksa key menjadi integer
                [
                    'nama_menu' => $item['title'] ?? 'Menu Kopi',
                    'harga'     => $item['price'] ?? 0,
                    'deskripsi' => $item['description'] ?? '',
                    'picUrl'    => $urlGambar,
                    'extra'     => $item['extra'] ?? '',
                    'quantity'  => $item['quantity'] ?? 0,
                    'kategori'  => 'Kopi',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        $this->command->info('Data Firebase (termasuk ID 0) berhasil sinkron!');
    }
}
}