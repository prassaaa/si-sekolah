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
        Schema::create('prestasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('nama_prestasi', 255);
            $table->enum('tingkat', ['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional']);
            $table->enum('jenis', ['akademik', 'non_akademik', 'olahraga', 'seni', 'keagamaan', 'lainnya']);
            $table->enum('peringkat', ['juara_1', 'juara_2', 'juara_3', 'harapan_1', 'harapan_2', 'harapan_3', 'peserta', 'lainnya'])->nullable();
            $table->string('penyelenggara', 255)->nullable();
            $table->date('tanggal');
            $table->string('bukti', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('siswa_id');
            $table->index('semester_id');
            $table->index('tingkat');
            $table->index('jenis');
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestasis');
    }
};
