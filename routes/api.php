<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CartController;
// --- A. Route Terbuka (Public) ---

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']); 
    Route::post('register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

// GET /api/menu (index) dan GET /api/menu/{id} (show)
Route::resource('menu', MenuController::class)->only(['index', 'show']); 


// --- B. Route Dilindungi oleh JWT ---

Route::middleware('auth.jwt')->group(function () { 
    
    // 1. Profil & Logout
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // 2. CRUD Menu (Admin/Authorized Only)
    Route::resource('menu', MenuController::class)->except(['index', 'show']);

    // 3. Wishlist System
    // POST /api/wishlist -> Tambah ke wishlist
    Route::post('wishlist', [WishlistController::class, 'store']);
    
    // GET /api/wishlist -> Lihat semua wishlist milik user yang login
    Route::get('wishlist', [WishlistController::class, 'index']);
    
    // DELETE /api/wishlist/{menu_id} -> Hapus berdasarkan menu_id
    // Ini yang bikin 404 kalau pakai Route::resource
    Route::delete('wishlist/{menu_id}', [WishlistController::class, 'destroy']);
    

    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::put('/cart/{menu_id}', [CartController::class, 'update']);
    Route::delete('cart/{menu_id}', [CartController::class, 'destroy']);
});
    


