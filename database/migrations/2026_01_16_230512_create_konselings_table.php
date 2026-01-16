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
        Schema::create('konselings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('konselor_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->date('tanggal');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->enum('jenis', ['individu', 'kelompok', 'keluarga'])->default('individu');
            $table->enum('kategori', ['akademik', 'pribadi', 'sosial', 'karir', 'lainnya'])->default('pribadi');
            $table->text('permasalahan');
            $table->text('hasil_konseling')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->enum('status', ['dijadwalkan', 'berlangsung', 'selesai', 'batal'])->default('dijadwalkan');
            $table->boolean('perlu_tindak_lanjut')->default(false);
            $table->date('tanggal_tindak_lanjut')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('siswa_id');
            $table->index('semester_id');
            $table->index('konselor_id');
            $table->index('tanggal');
            $table->index('status');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konselings');
    }
};
