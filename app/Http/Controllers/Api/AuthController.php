<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        // Middleware applied to protected methods
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token, 
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 180, // dalam menit waktu kedaluwarsa
            'user' => auth('api')->user()
        ]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8', 
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }
    
    public function changePassword(Request $request) {
        // 1. Ambil ID user yang sedang login via JWT
        $userId = auth('api')->id(); 
        
        // 2. Cari user di database menggunakan Model User agar method update() tersedia
        $user = User::find($userId);

        if (!$user) {            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        // 3. Validasi password lama
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ], 401); 
        }

        
        $user->password = Hash::make($request->new_password);
        $user->save(); 

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diganti'
        ]);
    }
    
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
    
    
    public function me()
    {
        return response()->json(auth('api')->user());
    }

   
}
?>