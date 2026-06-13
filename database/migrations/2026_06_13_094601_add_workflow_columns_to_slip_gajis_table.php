<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah jejak workflow Approve->Bayar pada slip gaji:
     *   - kas_keluar_id : tautan ke KasKeluar yang dibuat saat pembayaran
     *                     (terisi = sudah dibayar, dipakai sebagai kunci idempotensi).
     *   - approved_at   : stempel waktu approval (juga dipakai sebagai tanggal akrual).
     *   - paid_at       : stempel waktu pembayaran.
     */
    public function up(): void
    {
        Schema::table('slip_gajis', function (Blueprint $table): void {
            if (! Schema::hasColumn('slip_gajis', 'kas_keluar_id')) {
                $table->foreignId('kas_keluar_id')
                    ->nullable()
                    ->after('tanggal_bayar')
                    ->constrained('kas_keluars')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('slip_gajis', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('slip_gajis', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slip_gajis', function (Blueprint $table): void {
            if (Schema::hasColumn('slip_gajis', 'kas_keluar_id')) {
                $table->dropConstrainedForeignId('kas_keluar_id');
            }

            if (Schema::hasColumn('slip_gajis', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('slip_gajis', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
