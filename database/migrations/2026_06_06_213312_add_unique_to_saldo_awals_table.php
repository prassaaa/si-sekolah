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
        Schema::table('saldo_awals', function (Blueprint $table) {
            $table->unique(['akun_id', 'tahun_ajaran_id'], 'saldo_awals_akun_tahun_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saldo_awals', function (Blueprint $table) {
            $table->dropUnique('saldo_awals_akun_tahun_unique');
        });
    }
};
