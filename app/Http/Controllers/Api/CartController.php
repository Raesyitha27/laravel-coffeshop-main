<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * 1. Ambil semua isi keranjang milik user yang login
     * PERBAIKAN: Menggunakan Eager Loading 'with' untuk menyertakan data menu.
     */
    public function index()
    {
        // Pastikan Anda menggunakan guard 'api' jika menggunakan JWT/Sanctum
        $userId = auth('api')->id(); 

        // Mengambil data cart beserta relasi menu
        $carts = Cart::with('menu')
            ->where('user_id', $userId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $carts
        ], 200);
    }

    /**
     * 2. Tambah barang ke keranjang
     */
    public function store(Request $request)
    {
        $userId = auth('api')->id();

        $validator = Validator::make($request->all(), [
            'menu_id' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $cart = Cart::where('user_id', $userId)
                    ->where('menu_id', $request->menu_id)
                    ->first();

        if ($cart) {
            // Update quantity jika barang sudah ada
            $cart->update([
                'quantity' => $cart->quantity + $request->quantity
            ]);
        } else {
            // Buat record baru jika belum ada
            $cart = Cart::create([
                'user_id' => $userId,
                'menu_id' => $request->menu_id,
                'quantity' => $request->quantity
            ]);
        }

        // PERBAIKAN: Load relasi menu sebelum dikembalikan ke Android
        return response()->json([
            'success' => true,
            'message' => 'Berhasil masuk keranjang',
            'data' => $cart->load('menu')
        ], 201);
    }

    /**
     * 3. Update quantity (Plus/Minus dari Android)
     * PERBAIKAN: Menambahkan parameter quantityChange agar bisa dinamis (+1 atau -1)
     */
    public function update(Request $request, $menu_id)
    {
        $userId = auth('api')->id();
        
        // Android mengirim 'quantity' yang merupakan perubahan (misal: 1 atau -1)
        $cart = Cart::where('user_id', $userId)
                    ->where('menu_id', $menu_id)
                    ->first();

        if ($cart) {
            // Logika: quantity baru = quantity lama + quantity dari request
            $newQuantity = $cart->quantity + $request->quantity;

            if ($newQuantity <= 0) {
                $cart->delete();
                return response()->json(['success' => true, 'message' => 'Item dihapus'], 200);
            }

            $cart->update(['quantity' => $newQuantity]);

            return response()->json([
                'success' => true,
                'message' => 'Quantity diperbarui',
                'data' => $cart->load('menu')
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }

    /**
     * 4. Hapus item dari keranjang
     */
    public function destroy($menu_id)
    {
        $userId = auth('api')->id();
        $cart = Cart::where('user_id', $userId)
                    ->where('menu_id', $menu_id)
                    ->first();

        if ($cart) {
            $cart->delete();
            return response()->json(['success' => true, 'message' => 'Berhasil dihapus'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }
}