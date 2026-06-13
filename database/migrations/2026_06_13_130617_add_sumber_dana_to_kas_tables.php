<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom sumber_dana (enum BOS/komite/yayasan/lainnya) ke tabel
     * kas_masuks dan kas_keluars untuk mendukung BKU per sumber dana (F4).
     *
     * Kolom sumber (teks bebas) TETAP ada — sumber_dana adalah dimensi terpisah.
     */
    public function up(): void
    {
        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->enum('sumber_dana', ['bos', 'komite', 'yayasan', 'lainnya'])
                ->default('lainnya')
                ->after('sumber');

            $table->index('sumber_dana');
        });

        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->enum('sumber_dana', ['bos', 'komite', 'yayasan', 'lainnya'])
                ->default('lainnya')
                ->after('penerima');

            $table->index('sumber_dana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->dropIndex(['sumber_dana']);
            $table->dropColumn('sumber_dana');
        });

        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->dropIndex(['sumber_dana']);
            $table->dropColumn('sumber_dana');
        });
    }
};
