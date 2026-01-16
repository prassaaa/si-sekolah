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
        Schema::create('jenis_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_pembayaran_id')->constrained('kategori_pembayarans')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajarans')->cascadeOnDelete();
            $table->string('kode', 20);
            $table->string('nama', 100);
            $table->decimal('nominal', 15, 2);
            $table->enum('jenis', ['bulanan', 'tahunan', 'sekali_bayar', 'insidental'])->default('bulanan');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('kategori_pembayaran_id');
            $table->index('tahun_ajaran_id');
            $table->index('is_active');
            $table->unique(['kode', 'tahun_ajaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_pembayarans');
    }
};
