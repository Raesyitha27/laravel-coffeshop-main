<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;

class OrderController extends Controller
{
    protected $database;
    protected $menuTable = 'Popular'; // Nama node menu di Firebase

    public function __construct()
    {
        $path = storage_path('app/service-account.json');
        $url  = env('FIREBASE_DATABASE_URL');

        if (!is_readable($path)) {
            abort(500, 'Firebase service account tidak bisa dibaca di: ' . $path);
        }

        $factory = (new Factory)
            ->withServiceAccount($path)
            ->withDatabaseUri($url);

        $this->database = $factory->createDatabase();
    }

    /**
     * CHECKOUT / CREATE ORDER
     * POST /api/orders
     */
    public function store(Request $request)
    {
        // 1. VALIDASI REQUEST
        $validator = Validator::make($request->all(), [
            'total_price'      => 'required|numeric|min:0',
            'address'          => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.menu_id'  => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.size'     => 'required|string', // Tambahkan validasi size
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Mulai Transaksi Database (MySQL)
        DB::beginTransaction();

        try {
            // 2. SIMPAN DATA ORDER UTAMA KE MYSQL
            // Mengambil user_id dari token JWT yang aktif
            $orderId = DB::table('orders')->insertGetId([
                'user_id'     => auth()->id(), 
                'total_price' => $request->total_price,
                'status'      => 'pending',
                'address'     => $request->address,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 3. PROSES SETIAP ITEM
            foreach ($request->items as $item) {
                
                // --- A. CEK & UPDATE STOK DI FIREBASE ---
                $menuRef = $this->database
                    ->getReference($this->menuTable)
                    ->getChild($item['menu_id']);

                $menuData = $menuRef->getValue();

                if (!$menuData) {
                    throw new \Exception("Menu ID {$item['menu_id']} tidak ditemukan di Firebase.");
                }

                $currentQty = $menuData['quantity'] ?? 0;

                if ($currentQty < $item['quantity']) {
                    throw new \Exception("Stok untuk '" . ($menuData['title'] ?? $item['menu_id']) . "' tidak mencukupi.");
                }

                // Kurangi stok di Firebase
                $menuRef->update([
                    'quantity' => $currentQty - $item['quantity']
                ]);

                // --- B. SIMPAN ITEM KE MYSQL ---
                DB::table('order_items')->insert([
                    'order_id'   => $orderId,
                    'menu_id'    => $item['menu_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'size'       => $item['size'], // Masukkan data size
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Jika semua berhasil, simpan permanen
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil diproses',
                'order_id'=> $orderId
            ], 201);

        } catch (\Exception $e) {
            // Jika ada satu saja yang gagal, batalkan semua perubahan di MySQL
            // Catatan: Perubahan stok di Firebase tidak bisa di-rollback otomatis oleh Laravel
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan checkout: ' . $e->getMessage()
            ], 500);
        }
    }
}