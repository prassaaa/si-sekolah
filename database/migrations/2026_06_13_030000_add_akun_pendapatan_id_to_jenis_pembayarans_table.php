<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom akun_pendapatan_id ke tabel jenis_pembayarans.
     * Kolom ini memetakan setiap jenis pembayaran ke akun pendapatan (COA)
     * yang dikredit ketika Pembayaran berhasil diposting ke jurnal (basis kas).
     * Bila null, poster memakai akun pendapatan SPP default (4-1001).
     */
    public function up(): void
    {
        Schema::table('jenis_pembayarans', function (Blueprint $table) {
            $table->foreignId('akun_pendapatan_id')
                ->nullable()
                ->after('nominal')
                ->constrained('akuns')
                ->nullOnDelete();

            $table->index('akun_pendapatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_pembayarans', function (Blueprint $table) {
            $table->dropForeign(['akun_pendapatan_id']);
            $table->dropIndex(['akun_pendapatan_id']);
            $table->dropColumn('akun_pendapatan_id');
        });
    }
};
