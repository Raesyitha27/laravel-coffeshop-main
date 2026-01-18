<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use App\Models\Menu;
use App\Models\Order;

class OrderController extends Controller
{
    protected $database;

    public function __construct()
    {
        $path = storage_path('app/service-account.json');
        $url  = env('FIREBASE_DATABASE_URL');

        $factory = (new Factory)
            ->withServiceAccount($path)
            ->withDatabaseUri($url);

        $this->database = $factory->createDatabase();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_price'      => 'required|numeric',
            'address'          => 'required|string',
            'items'            => 'required|array',
            'items.*.menu_id'  => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.price'    => 'required|numeric',
            'items.*.size'     => 'required|string', 
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Simpan Order Utama
            $order = Order::create([
                'user_id'     => auth()->id(),
                'total_price' => $request->total_price,
                'status'      => 'Proses',
                'address'     => $request->address,
            ]);

            // 2. Simpan Items & Update Stok
            foreach ($request->items as $item) {
                // Update Firebase Stock
                $menuRef = $this->database->getReference('Popular')->getChild($item['menu_id']);
                $currentQty = $menuRef->getValue()['quantity'] ?? 0;
                $menuRef->update(['quantity' => $currentQty - $item['quantity']]);

                // Update MySQL Stock
                Menu::where('id', $item['menu_id'])->decrement('quantity', $item['quantity']);

                // Simpan Order Item
                DB::table('order_items')->insert([
                    'order_id'   => $order->id,
                    'menu_id'    => $item['menu_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                    'size'       => $item['size'],
                    'created_at' => now(),
                ]);
            }

            // Bagian akhir fungsi store di OrderController.php
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => [
                    'id' => $order->id, // Memasukkan ID ke dalam objek 'data'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}