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
        Schema::create('kelulusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->string('nomor_ijazah', 50)->nullable();
            $table->string('nomor_skhun', 50)->nullable();
            $table->date('tanggal_lulus');
            $table->enum('status', ['lulus', 'tidak_lulus', 'pending'])->default('pending');
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('predikat', 50)->nullable();
            $table->string('tujuan_sekolah')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('siswa_id');
            $table->index('tahun_ajaran_id');
            $table->index('status');
            $table->unique(['siswa_id', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelulusans');
    }
};
