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
        Schema::create('akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 100);
            $table->enum('tipe', ['aset', 'liabilitas', 'ekuitas', 'pendapatan', 'beban']);
            $table->enum('kategori', ['lancar', 'tetap', 'jangka_panjang', 'operasional', 'non_operasional'])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('akuns')->nullOnDelete();
            $table->text('deskripsi')->nullable();
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->enum('posisi_normal', ['debit', 'kredit']);
            $table->boolean('is_active')->default(true);
            $table->integer('level')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tipe');
            $table->index('is_active');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akuns');
    }
};
