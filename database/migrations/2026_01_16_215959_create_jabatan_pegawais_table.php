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
        Schema::create('jabatan_pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->enum('jenis', ['Struktural', 'Fungsional', 'Non-Fungsional'])->default('Fungsional');
            $table->enum('golongan', ['I', 'II', 'III', 'IV', 'Non-PNS'])->nullable();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan', 15, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nama');
            $table->index('jenis');
            $table->index('golongan');
            $table->index('is_active');
            $table->index('urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatan_pegawais');
    }
};
