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
        Schema::create('slip_gajis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->foreignId('pegawai_id')->constrained()->cascadeOnDelete();
            $table->foreignId('setting_gaji_id')->nullable()->constrained()->nullOnDelete();
            $table->year('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);
            $table->json('detail_tunjangan')->nullable();
            $table->json('detail_potongan')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->date('tanggal_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pegawai_id');
            $table->index(['tahun', 'bulan']);
            $table->index('status');
            $table->unique(['pegawai_id', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slip_gajis');
    }
};
