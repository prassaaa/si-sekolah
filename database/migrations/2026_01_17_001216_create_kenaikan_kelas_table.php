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
        Schema::create('kenaikan_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('kelas_asal_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('kelas_tujuan_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->enum('status', ['naik', 'tinggal', 'mutasi_keluar', 'pending'])->default('pending');
            $table->decimal('nilai_rata_rata', 5, 2)->nullable();
            $table->integer('peringkat')->nullable();
            $table->text('catatan')->nullable();
            $table->date('tanggal_keputusan')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('siswa_id');
            $table->index('semester_id');
            $table->index('kelas_asal_id');
            $table->index('status');
            $table->unique(['siswa_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kenaikan_kelas');
    }
};
