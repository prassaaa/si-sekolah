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
        Schema::create('sarpras_pengadaans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->enum('sumber_dana', ['bos', 'komite', 'yayasan', 'hibah', 'pribadi', 'lainnya'])->default('bos');
            $table->string('penyedia')->nullable();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->enum('status', ['draft', 'disetujui', 'diterima', 'batal'])->default('draft');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('tanggal');
            $table->index('dibuat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_pengadaans');
    }
};
