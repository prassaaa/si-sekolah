<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom kas_akun_id ke tabel kas_masuks dan kas_keluars.
     * Kolom ini menyimpan akun kas/bank yang menjadi sisi kas dalam jurnal,
     * berbeda dari akun_id yang adalah akun lawan (pendapatan/beban).
     */
    public function up(): void
    {
        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->foreignId('kas_akun_id')
                ->nullable()
                ->after('akun_id')
                ->constrained('akuns')
                ->nullOnDelete();

            $table->index('kas_akun_id');
        });

        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->foreignId('kas_akun_id')
                ->nullable()
                ->after('akun_id')
                ->constrained('akuns')
                ->nullOnDelete();

            $table->index('kas_akun_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_masuks', function (Blueprint $table) {
            $table->dropForeign(['kas_akun_id']);
            $table->dropIndex(['kas_akun_id']);
            $table->dropColumn('kas_akun_id');
        });

        Schema::table('kas_keluars', function (Blueprint $table) {
            $table->dropForeign(['kas_akun_id']);
            $table->dropIndex(['kas_akun_id']);
            $table->dropColumn('kas_akun_id');
        });
    }
};
