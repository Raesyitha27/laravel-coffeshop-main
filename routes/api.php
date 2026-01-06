<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- A. Route Terbuka (Public) ---
// Route di sini bisa diakses tanpa login/token
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']); 
    Route::post('register', [AuthController::class, 'register']);
});

// Route menu index dan show tetap public agar orang bisa lihat menu tanpa login
Route::resource('menu', MenuController::class)->only(['index', 'show']); 


// --- B. Route Dilindungi oleh JWT ---
// Semua route di dalam grup ini WAJIB mengirimkan Token Bearer dari Android
Route::middleware('auth.jwt')->group(function () { 
    
    // 1. Grup Auth (Logout, Me, Change Password)
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        
        /** * PERBAIKAN: Change Password dipindahkan ke sini agar Auth::user() tidak null 
         * URL: /api/auth/change-password
         */
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });

    // 2. CRUD Menu (Khusus yang membutuhkan login)
    Route::resource('menu', MenuController::class)->except(['index', 'show']);

    // 3. Wishlist System
    Route::post('wishlist', [WishlistController::class, 'store']);
    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::delete('wishlist/{menu_id}', [WishlistController::class, 'destroy']);
    
    // 4. Cart System
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart', [CartController::class, 'store']);
    Route::put('/cart/{menu_id}', [CartController::class, 'update']);
    Route::delete('cart/{menu_id}', [CartController::class, 'destroy']);
});