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
        Schema::create('sarpras_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_inventaris')->unique();
            $table->string('nama');
            $table->foreignId('sarpras_kategori_id')->constrained()->restrictOnDelete();
            $table->foreignId('ruangan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipe', ['aset', 'bahan'])->default('aset');
            $table->string('merk')->nullable();
            $table->text('spesifikasi')->nullable();
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->enum('status', ['tersedia', 'dipinjam', 'perbaikan', 'dihapus'])->default('tersedia');
            $table->enum('sumber_dana', ['bos', 'komite', 'yayasan', 'hibah', 'pribadi', 'lainnya'])->default('bos');
            $table->year('tahun_perolehan')->nullable();
            $table->decimal('harga_perolehan', 15, 2)->default(0);
            $table->integer('jumlah')->default(1);
            $table->string('satuan')->default('unit');
            $table->string('foto')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sarpras_kategori_id');
            $table->index('ruangan_id');
            $table->index('tipe');
            $table->index('kondisi');
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_barangs');
    }
};
