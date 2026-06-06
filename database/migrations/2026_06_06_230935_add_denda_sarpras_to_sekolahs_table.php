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
        Schema::table('sekolahs', function (Blueprint $table) {
            $table->decimal('tarif_denda_sarpras_per_hari', 15, 2)->default(0)->after('is_active');
            $table->integer('maks_denda_persen')->default(50)->after('tarif_denda_sarpras_per_hari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sekolahs', function (Blueprint $table) {
            $table->dropColumn(['tarif_denda_sarpras_per_hari', 'maks_denda_persen']);
        });
    }
};
