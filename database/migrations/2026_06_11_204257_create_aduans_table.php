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
        Schema::create('aduans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
            $table->string('pelapor');
            $table->enum('hubungan_pelapor', ['siswa', 'ayah', 'ibu', 'wali', 'lainnya'])->default('lainnya');
            $table->string('kontak_pelapor')->nullable();
            $table->date('tanggal_aduan');
            $table->enum('kategori', ['akademik', 'fasilitas', 'perlakuan', 'keuangan', 'lainnya'])->default('lainnya');
            $table->string('judul');
            $table->text('isi');
            $table->string('lampiran')->nullable();
            $table->enum('status', ['baru', 'diproses', 'selesai', 'ditolak'])->default('baru');
            $table->foreignId('ditangani_oleh')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->text('tanggapan')->nullable();
            $table->timestamp('tanggal_tanggapan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('kategori');
            $table->index('tanggal_aduan');
            $table->index('siswa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aduans');
    }
};
