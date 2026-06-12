<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hapus constraint UNIQUE pada nomor_bukti dan ganti dengan index biasa
     * supaya beberapa baris jurnal bisa berbagi satu nomor bukti (double-entry).
     */
    public function up(): void
    {
        Schema::table('jurnal_umums', function (Blueprint $table) {
            $table->dropUnique('jurnal_umums_nomor_bukti_unique');
            $table->index('nomor_bukti', 'jurnal_umums_nomor_bukti_index');
        });
    }

    /**
     * Kembalikan ke constraint UNIQUE (hati-hati: akan gagal jika data sudah
     * memiliki nomor_bukti duplikat dari entri double-entry).
     */
    public function down(): void
    {
        Schema::table('jurnal_umums', function (Blueprint $table) {
            $table->dropIndex('jurnal_umums_nomor_bukti_index');
            $table->unique('nomor_bukti', 'jurnal_umums_nomor_bukti_unique');
        });
    }
};
