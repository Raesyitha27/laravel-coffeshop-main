<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::where('user_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'data' => $wishlists
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => auth()->id(), 
            'menu_id' => $request->menu_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan ke Wishlist',
            'data' => $wishlist
        ], 201);
    }
    
    // PERBAIKAN DI SINI: Gunakan parameter $menu_id biasa, bukan Model Binding
    public function destroy($menu_id)
    {
        // Cari data berdasarkan user_id yang login DAN menu_id dari Android/Postman
        $wishlist = Wishlist::where('user_id', auth()->id())
                            ->where('menu_id', $menu_id)
                            ->first();

        // Jika data tidak ditemukan
        if (!$wishlist) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan di wishlist Anda.'
            ], 404);
        }
        
        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari Wishlist'
        ], 200);
    }
}