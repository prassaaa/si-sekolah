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
        Schema::create('tagihan_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('jenis_pembayaran_id')->constrained('jenis_pembayarans')->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->string('nomor_tagihan', 50)->unique();
            $table->decimal('nominal', 15, 2);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total_tagihan', 15, 2);
            $table->decimal('total_terbayar', 15, 2)->default(0);
            $table->decimal('sisa_tagihan', 15, 2);
            $table->date('tanggal_tagihan');
            $table->date('tanggal_jatuh_tempo');
            $table->enum('status', ['belum_bayar', 'sebagian', 'lunas', 'batal'])->default('belum_bayar');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('siswa_id');
            $table->index('jenis_pembayaran_id');
            $table->index('status');
            $table->index('tanggal_jatuh_tempo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_siswas');
    }
};
