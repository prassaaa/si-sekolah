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
        Schema::create('sarpras_penghapusans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('sarpras_barang_id')->constrained()->restrictOnDelete();
            $table->date('tanggal');
            $table->enum('alasan', ['rusak_berat', 'hilang', 'usang', 'lainnya'])->default('rusak_berat');
            $table->integer('jumlah')->default(1);
            $table->decimal('nilai_sisa', 15, 2)->default(0);
            $table->enum('metode', ['dibuang', 'dijual', 'disumbangkan'])->default('dibuang');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sarpras_barang_id');
            $table->index('status');
            $table->index('tanggal');
            $table->index('disetujui_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_penghapusans');
    }
};
