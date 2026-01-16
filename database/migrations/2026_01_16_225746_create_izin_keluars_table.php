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
        Schema::create('izin_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_keluar');
            $table->time('jam_kembali')->nullable();
            $table->string('keperluan', 255);
            $table->string('tujuan', 255)->nullable();
            $table->string('penjemput_nama', 100)->nullable();
            $table->string('penjemput_hubungan', 50)->nullable();
            $table->string('penjemput_telepon', 20)->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->enum('status', ['diizinkan', 'ditolak', 'pending'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('siswa_id');
            $table->index('tanggal');
            $table->index('status');
            $table->index('petugas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izin_keluars');
    }
};
