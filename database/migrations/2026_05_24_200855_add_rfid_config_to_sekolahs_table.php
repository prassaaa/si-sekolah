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
            $table->time('jam_masuk_default')->default('07:00:00')->after('is_active');
            $table->unsignedSmallInteger('batas_terlambat_menit')->default(15)->after('jam_masuk_default');
            $table->time('jam_pulang_minimal')->default('12:00:00')->after('batas_terlambat_menit');
            $table->unsignedSmallInteger('debounce_scan_detik')->default(60)->after('jam_pulang_minimal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sekolahs', function (Blueprint $table) {
            $table->dropColumn([
                'jam_masuk_default',
                'batas_terlambat_menit',
                'jam_pulang_minimal',
                'debounce_scan_detik',
            ]);
        });
    }
};
