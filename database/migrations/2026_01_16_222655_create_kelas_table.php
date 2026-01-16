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
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->string('nama', 20)->comment('1A, 2B, 7A, X IPA 1');
            $table->tinyInteger('tingkat')->comment('1-6 SD, 7-9 SMP, 10-12 SMA');
            $table->string('jurusan', 50)->nullable()->comment('IPA, IPS, Bahasa, dll');
            $table->foreignId('wali_kelas_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->integer('kapasitas')->default(30);
            $table->string('ruangan', 20)->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['tahun_ajaran_id', 'nama']);
            $table->index('nama');
            $table->index('tingkat');
            $table->index('is_active');
            $table->index('wali_kelas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
