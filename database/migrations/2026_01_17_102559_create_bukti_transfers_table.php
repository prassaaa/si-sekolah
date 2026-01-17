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
        Schema::create('bukti_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tagihan_siswa_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama_pengirim');
            $table->string('bank_pengirim');
            $table->string('nomor_rekening')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->date('tanggal_transfer');
            $table->string('bukti_file')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('catatan_wali')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('siswa_id');
            $table->index('tagihan_siswa_id');
            $table->index('status');
            $table->index('tanggal_transfer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukti_transfers');
    }
};
