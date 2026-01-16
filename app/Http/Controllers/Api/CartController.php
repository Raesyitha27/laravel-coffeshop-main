<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    
    public function index()
    {
        $carts = Cart::where('user_id', auth()->id())->get();
        return response()->json([
            'success' => true,
            'data' => $carts
        ], 200);
    }

    // 2. Tambah barang ke keranjang
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cari dulu apakah barang sudah ada di cart user tersebut
        $cart = Cart::where('user_id', auth()->id())
                    ->where('menu_id', $request->menu_id)
                    ->first();

        if ($cart) {
            // Jika ada, tambahkan quantity-nya
            $cart->update([
                'quantity' => $cart->quantity + $request->quantity
            ]);
        } else {
            // Jika tidak ada, buat baru
            $cart = Cart::create([
                'user_id' => auth()->id(),
                'menu_id' => $request->menu_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil masuk keranjang',
            'data' => $cart
        ], 201);
    }

    // 4. Update quantity (bisa untuk tambah/kurang dari Android)
    public function update(Request $request, $menu_id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cari item berdasarkan user_id dan menu_id
        $cart = Cart::where('user_id', auth()->id())
                    ->where('menu_id', $menu_id)
                    ->first();

        if ($cart) {
            $cart->update([
                'quantity' => $request->quantity
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quantity berhasil diperbarui',
                'data' => $cart
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Item tidak ditemukan di keranjang'
        ], 404);
    }   

    // 3. Hapus item dari keranjang
    public function destroy($menu_id)
    {
        $cart = Cart::where('user_id', auth()->id())
                    ->where('menu_id', $menu_id)
                    ->first();

        if ($cart) {
            $cart->delete();
            return response()->json(['success' => true, 'message' => 'Berhasil dihapus'], 200);
        }

        return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }
}