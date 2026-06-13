<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah potongan PPh (pajak flat) pada slip gaji:
     *   - pajak_id        : tautan ke master Pajak yang dipakai sebagai dasar
     *                       persentase potongan (nullable; nullOnDelete agar
     *                       penghapusan master pajak tidak menghapus slip).
     *   - potongan_pajak  : nominal potongan pajak hasil hitung ulang server
     *                       (persentase% x (gaji_pokok + total_tunjangan)).
     *
     * Aditif terhadap flow gaji Wave 2: default 0 sehingga slip tanpa pajak
     * berperilaku persis seperti sebelumnya.
     */
    public function up(): void
    {
        Schema::table('slip_gajis', function (Blueprint $table): void {
            if (! Schema::hasColumn('slip_gajis', 'pajak_id')) {
                $table->foreignId('pajak_id')
                    ->nullable()
                    ->after('setting_gaji_id')
                    ->constrained('pajaks')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('slip_gajis', 'potongan_pajak')) {
                $table->decimal('potongan_pajak', 15, 2)
                    ->default(0)
                    ->after('total_potongan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slip_gajis', function (Blueprint $table): void {
            if (Schema::hasColumn('slip_gajis', 'pajak_id')) {
                $table->dropConstrainedForeignId('pajak_id');
            }

            if (Schema::hasColumn('slip_gajis', 'potongan_pajak')) {
                $table->dropColumn('potongan_pajak');
            }
        });
    }
};
