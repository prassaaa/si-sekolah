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
        Schema::create('sarpras_pemeliharaans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('sarpras_barang_id')->constrained()->restrictOnDelete();
            $table->enum('jenis', ['rutin', 'perbaikan', 'kalibrasi'])->default('rutin');
            $table->date('tanggal');
            $table->date('tanggal_selesai')->nullable();
            $table->text('deskripsi_masalah');
            $table->text('tindakan')->nullable();
            $table->enum('pelaksana', ['internal', 'vendor'])->default('internal');
            $table->string('nama_vendor')->nullable();
            $table->decimal('biaya', 15, 2)->default(0);
            $table->enum('kondisi_sebelum', ['baik', 'rusak_ringan', 'rusak_berat'])->nullable();
            $table->enum('kondisi_sesudah', ['baik', 'rusak_ringan', 'rusak_berat'])->nullable();
            $table->enum('status', ['dijadwalkan', 'proses', 'selesai', 'batal'])->default('dijadwalkan');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sarpras_barang_id');
            $table->index('jenis');
            $table->index('status');
            $table->index('tanggal');
            $table->index('dicatat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_pemeliharaans');
    }
};
