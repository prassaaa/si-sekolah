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
        Schema::create('mata_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('singkatan', 10)->nullable();
            $table->string('kelompok', 50)->nullable()->comment('Kelompok A/B/C, Muatan Lokal, dll');
            $table->enum('jenjang', ['TK', 'SD', 'SMP', 'SMA', 'SMK'])->nullable();
            $table->tinyInteger('jam_per_minggu')->default(2);
            $table->integer('kkm')->default(75)->comment('Kriteria Ketuntasan Minimal');
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->index('nama');
            $table->index('kelompok');
            $table->index('jenjang');
            $table->index('is_active');
            $table->index('urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};
