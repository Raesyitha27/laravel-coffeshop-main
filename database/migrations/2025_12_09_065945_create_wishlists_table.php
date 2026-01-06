<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('wishlists', function (Blueprint $table) {
        // Hapus foreign key. Nama constraint biasanya 'wishlists_menu_id_foreign'
        $table->dropForeign(['menu_id']);

        // Jika menu_id sebelumnya integer, ubah ke string agar cocok dengan ID Firebase
        $table->string('menu_id')->change(); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
