<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan dimensi periode pada tagihan bulanan (SPP) agar generate
     * tagihan massal idempoten per (siswa, jenis, bulan, tahun). Untuk tagihan
     * non-bulanan kedua kolom tetap null. Kolom nullable + index idempoten agar
     * aman dijalankan di server produksi berisi data nyata.
     */
    public function up(): void
    {
        Schema::table('tagihan_siswas', function (Blueprint $table): void {
            if (! Schema::hasColumn('tagihan_siswas', 'periode_bulan')) {
                $table->unsignedTinyInteger('periode_bulan')->nullable()->after('semester_id');
            }

            if (! Schema::hasColumn('tagihan_siswas', 'periode_tahun')) {
                $table->unsignedSmallInteger('periode_tahun')->nullable()->after('periode_bulan');
            }
        });

        Schema::table('tagihan_siswas', function (Blueprint $table): void {
            $table->index(
                ['siswa_id', 'jenis_pembayaran_id', 'periode_bulan', 'periode_tahun'],
                'tagihan_siswas_periode_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihan_siswas', function (Blueprint $table): void {
            $table->dropIndex('tagihan_siswas_periode_index');
            $table->dropColumn(['periode_bulan', 'periode_tahun']);
        });
    }
};
