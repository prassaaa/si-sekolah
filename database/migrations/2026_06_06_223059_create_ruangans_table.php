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
        Schema::create('ruangans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->enum('jenis', ['kelas', 'lab', 'kantor', 'gudang', 'perpustakaan', 'aula', 'lainnya']);
            $table->string('gedung')->nullable();
            $table->integer('lantai')->nullable();
            $table->integer('kapasitas')->nullable();
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('pegawais')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('jenis');
            $table->index('is_active');
            $table->index('penanggung_jawab_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruangans');
    }
};
