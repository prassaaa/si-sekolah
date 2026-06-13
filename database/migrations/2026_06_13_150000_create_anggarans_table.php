<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained()->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal_anggaran', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tahun_ajaran_id', 'akun_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggarans');
    }
};
