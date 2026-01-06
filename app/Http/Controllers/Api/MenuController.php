<?php
// app/Http/Controllers/Api/MenuController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Exception\FirebaseException; // Import untuk menangani error spesifik Firebase

class MenuController extends Controller
{
    protected $database;
    protected $table = 'Popular'; // Node di Firebase RTDB Anda

 public function __construct()
{
    // Gunakan DIRECTORY_SEPARATOR agar otomatis menyesuaikan Windows (\) atau Linux (/)
    $path = storage_path('app' . DIRECTORY_SEPARATOR . 'service-account.json'); 
    $url = env('FIREBASE_DATABASE_URL');

    // Tambahkan pengecekan manual sebelum factory memproses
    if (!is_readable($path)) {
        die("File JSON ada tapi tidak bisa dibaca oleh sistem. Cek permission file.");
    }

    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount($path)
        ->withDatabaseUri($url);

    $this->database = $factory->createDatabase();
}
    // --- READ: Tampilkan semua menu (GET /api/menu) ---
    public function index()
    {
        if ($this->database === null) {
             return response()->json(['success' => false, 'message' => 'Firebase Service Not Initialized'], 500);
        }

        try {
            // Ambil semua data dari node 'Items' di Firebase
            $menus = $this->database->getReference($this->table)->getValue(); 

            // Jika $menus null/kosong (Node tidak ada), kembalikan array kosong
            if ($menus === null) {
                $menus = [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Daftar Menu berhasil diambil dari Firebase',
                // Data dikirim dalam bentuk array asosiatif (Map<String, Item> di Android)
                'data' => $menus 
            ], 200);

        } catch (FirebaseException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Firebase Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // --- READ: Tampilkan detail menu (GET /api/menu/{itemId}) ---
    public function show($itemId)
    {
        if ($this->database === null) {
             return response()->json(['success' => false, 'message' => 'Firebase Service Not Initialized'], 500);
        }

        try {
            // Ambil data berdasarkan kunci (itemId)
            $menu = $this->database->getReference($this->table)->getChild($itemId)->getValue();

            if ($menu === null) {
                return response()->json(['success' => false, 'message' => 'Menu tidak ditemukan'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $menu
            ], 200);

        } catch (FirebaseException $e) {
            return response()->json(['success' => false, 'message' => 'Firebase Error: ' . $e->getMessage()], 500);
        }
    }
    
    // --- CREATE: Tambah menu baru (POST /api/menu) ---
    public function store(Request $request)
    {
        if ($this->database === null) {
             return response()->json(['success' => false, 'message' => 'Firebase Service Not Initialized'], 500);
        }

        // 🟢 Validasi harus sesuai dengan struktur data Firebase Anda
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255', 
            'price' => 'required|numeric|min:0',
            'categoryId' => 'required|string|max:100',
            // Tambahkan validasi untuk field lain (description, picUrl, dll.)
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); 
        }

        try {
            // PUSH data ke Firebase, Firebase akan membuat ID acak unik
            $newPost = $this->database->getReference($this->table)->push($request->all());
            $newKey = $newPost->getKey();
            
            return response()->json([
                'success' => true,
                'message' => 'Menu berhasil ditambahkan ke Firebase',
                'data' => $request->all(),
                'key' => $newKey 
            ], 201);

        } catch (FirebaseException $e) {
             return response()->json(['success' => false, 'message' => 'Gagal menyimpan data ke Firebase: ' . $e->getMessage()], 500);
        }
    }

    // --- UPDATE: Edit menu (PUT /api/menu/{itemId}) ---
    public function update(Request $request, $itemId)
    {
        if ($this->database === null) {
             return response()->json(['success' => false, 'message' => 'Firebase Service Not Initialized'], 500);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255', 
            'price' => 'required|numeric|min:0',
            'categoryId' => 'required|string|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // SET: Mengganti seluruh data pada kunci $itemId
            $this->database->getReference($this->table)->getChild($itemId)->set($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Menu berhasil diperbarui di Firebase',
                'data' => $request->all()
            ], 200);

        } catch (FirebaseException $e) {
             return response()->json(['success' => false, 'message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    // --- DELETE: Hapus menu (DELETE /api/menu/{itemId}) ---
    public function destroy($itemId)
    {
        if ($this->database === null) {
             return response()->json(['success' => false, 'message' => 'Firebase Service Not Initialized'], 500);
        }

        try {
            // REMOVE: Menghapus node berdasarkan kunci $itemId
            $this->database->getReference($this->table)->getChild($itemId)->remove();

            return response()->json([
                'success' => true,
                'message' => 'Menu berhasil dihapus dari Firebase'
            ], 200);

        } catch (FirebaseException $e) {
             return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}