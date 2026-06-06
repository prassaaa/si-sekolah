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
        Schema::table('sarpras_barangs', function (Blueprint $table) {
            $table->enum('metode_susut', ['garis_lurus', 'saldo_menurun', 'tanpa'])->default('tanpa')->after('tahun_perolehan');
            $table->integer('umur_ekonomis_bulan')->nullable()->after('metode_susut');
            $table->decimal('nilai_residu', 15, 2)->default(0)->after('umur_ekonomis_bulan');
            $table->date('tanggal_perolehan')->nullable()->after('nilai_residu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sarpras_barangs', function (Blueprint $table) {
            $table->dropColumn(['metode_susut', 'umur_ekonomis_bulan', 'nilai_residu', 'tanggal_perolehan']);
        });
    }
};
