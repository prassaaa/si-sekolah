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
        Schema::create('sarpras_peminjamans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('sarpras_barang_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('peminjam');
            $table->integer('jumlah')->default(1);
            $table->date('tanggal_pinjam');
            $table->date('tanggal_harus_kembali');
            $table->date('tanggal_kembali')->nullable();
            $table->enum('kondisi_pinjam', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->enum('kondisi_kembali', ['baik', 'rusak_ringan', 'rusak_berat'])->nullable();
            $table->enum('status', ['dipinjam', 'dikembalikan', 'terlambat', 'hilang'])->default('dipinjam');
            $table->foreignId('petugas_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sarpras_barang_id');
            $table->index('status');
            $table->index('tanggal_pinjam');
            $table->index('petugas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_peminjamans');
    }
};
