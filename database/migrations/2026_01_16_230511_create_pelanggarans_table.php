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
        Schema::create('pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('jenis_pelanggaran', 255);
            $table->enum('kategori', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->unsignedSmallInteger('poin')->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('bukti', 255)->nullable();
            $table->foreignId('pelapor_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->enum('status', ['proses', 'selesai', 'batal'])->default('proses');
            $table->string('tindak_lanjut', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('siswa_id');
            $table->index('semester_id');
            $table->index('tanggal');
            $table->index('kategori');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
    }
};
