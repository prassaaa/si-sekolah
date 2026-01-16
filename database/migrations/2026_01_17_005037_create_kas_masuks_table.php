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
        Schema::create('kas_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bukti')->unique();
            $table->foreignId('akun_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->string('sumber')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('akun_id');
            $table->index('tanggal');
            $table->index('nomor_bukti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_masuks');
    }
};
