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
        Schema::create('pembayaran_pakets', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('tahun_ajaran_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tahun_ajaran_id');
            $table->index('is_active');
        });

        // Pivot table for pembayaran paket items
        Schema::create('pembayaran_paket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_paket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jenis_pembayaran_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->timestamps();

            $table->unique(['pembayaran_paket_id', 'jenis_pembayaran_id'], 'paket_jenis_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_paket_items');
        Schema::dropIfExists('pembayaran_pakets');
    }
};
