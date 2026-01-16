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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_siswa_id')->constrained('tagihan_siswas')->cascadeOnDelete();
            $table->string('nomor_transaksi', 50)->unique();
            $table->date('tanggal_bayar');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->enum('metode_pembayaran', ['tunai', 'transfer', 'qris', 'virtual_account', 'lainnya'])->default('tunai');
            $table->string('referensi_pembayaran')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['pending', 'berhasil', 'gagal', 'batal'])->default('berhasil');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tagihan_siswa_id');
            $table->index('tanggal_bayar');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
