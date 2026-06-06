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
        Schema::create('sarpras_pengadaan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sarpras_pengadaan_id')->constrained()->cascadeOnDelete();
            $table->string('nama_barang');
            $table->foreignId('sarpras_kategori_id')->constrained()->restrictOnDelete();
            $table->integer('jumlah');
            $table->string('satuan')->default('unit');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->index('sarpras_pengadaan_id');
            $table->index('sarpras_kategori_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_pengadaan_items');
    }
};
