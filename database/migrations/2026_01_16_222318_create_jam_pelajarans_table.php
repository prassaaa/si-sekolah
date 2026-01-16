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
        Schema::create('jam_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('jam_ke');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->integer('durasi')->comment('Durasi dalam menit');
            $table->enum('jenis', ['Reguler', 'Istirahat', 'Upacara', 'Ekstrakurikuler'])->default('Reguler');
            $table->string('keterangan', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['jam_ke', 'jenis']);
            $table->index('jam_ke');
            $table->index('jenis');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jam_pelajarans');
    }
};
