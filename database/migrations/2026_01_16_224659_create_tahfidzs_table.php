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
        Schema::create('tahfidzs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('penguji_id')->nullable()->constrained('pegawais')->nullOnDelete();

            $table->string('surah', 50);
            $table->unsignedSmallInteger('ayat_mulai');
            $table->unsignedSmallInteger('ayat_selesai');
            $table->unsignedSmallInteger('jumlah_ayat');
            $table->unsignedTinyInteger('juz')->nullable();

            $table->date('tanggal');
            $table->enum('jenis', ['setoran', 'murojaah', 'ujian'])->default('setoran');
            $table->enum('status', ['lulus', 'mengulang', 'pending'])->default('pending');
            $table->unsignedTinyInteger('nilai')->nullable()->comment('Nilai 0-100');
            $table->text('catatan')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('siswa_id');
            $table->index('semester_id');
            $table->index('penguji_id');
            $table->index('tanggal');
            $table->index('surah');
            $table->index('status');
            $table->index('jenis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahfidzs');
    }
};
