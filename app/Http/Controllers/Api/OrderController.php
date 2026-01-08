<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan milik user yang sedang login.
     */
    public function index()
    {
        // Mengambil order milik user yang login beserta item-itemnya
        $orders = Order::with('items')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil',
            'data'    => $orders
        ], 200);
    }

    /**
     * Menyimpan pesanan baru (Checkout).
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'total_price'      => 'required|numeric',
            'address'          => 'nullable|string',
            'items'            => 'required|array',
            'items.*.menu_id'  => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price'    => 'required|numeric',
            'items.*.size'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Menggunakan Transaction agar jika salah satu gagal, semua dibatalkan
            DB::beginTransaction();

            // 2. Simpan ke tabel 'orders'
            $order = Order::create([
                'user_id'     => auth()->id(), // Mengambil ID dari token JWT
                'total_price' => $request->total_price,
                'status'      => 'pending',
                'address'     => $request->address,
            ]);

            // 3. Simpan setiap item ke tabel 'order_items'
            foreach ($request->items as $item) {
                $order->items()->create([
                    'menu_id'  => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                    'size'     => $item['size'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data'    => $order->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu pesanan berdasarkan ID.
     */
    public function show($id)
    {
        $order = Order::with('items')->where('user_id', auth()->id())->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $order
        ], 200);
    }

    /**
     * Mengupdate status pesanan (Misal untuk Admin mengubah ke 'success').
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->update([
            'status' => $request->status // Kirim 'success' atau 'failed' dari Postman
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan diperbarui',
            'data'    => $order
        ], 200);
    }
}