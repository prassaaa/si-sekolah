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
        Schema::create('informasis', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->enum('kategori', ['Pengumuman', 'Berita', 'Kegiatan', 'Prestasi', 'Lainnya'])->default('Pengumuman');
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->string('gambar')->nullable();
            $table->enum('prioritas', ['Rendah', 'Normal', 'Tinggi', 'Urgent'])->default('Normal');
            $table->date('tanggal_publish')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index('judul');
            $table->index('kategori');
            $table->index('prioritas');
            $table->index('tanggal_publish');
            $table->index('is_published');
            $table->index('is_pinned');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informasis');
    }
};
