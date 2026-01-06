<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::create('carts', function (Blueprint $table) {
        $table->id();
        // Menghubungkan ke tabel users (otomatis mengambil id user yang login)
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        $table->string('menu_id'); // ID Menu dari Android
        $table->integer('quantity')->default(1);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
