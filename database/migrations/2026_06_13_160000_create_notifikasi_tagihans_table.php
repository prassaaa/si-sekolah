<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('notifikasi_tagihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_siswa_id')->nullable()->constrained('tagihan_siswas')->nullOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->string('tujuan_nomor', 20);
            $table->text('pesan');
            $table->enum('status', ['antri', 'terkirim', 'gagal'])->default('antri');
            $table->string('driver', 50);
            $table->text('response')->nullable();
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('siswa_id');
            $table->index('tagihan_siswa_id');
            $table->index('status');
        });
    }

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi_tagihans');
    }
};
