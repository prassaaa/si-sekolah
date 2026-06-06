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
        Schema::table('sarpras_peminjamans', function (Blueprint $table) {
            $table->decimal('denda', 15, 2)->default(0)->after('catatan');
            $table->integer('hari_telat')->default(0)->after('denda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sarpras_peminjamans', function (Blueprint $table) {
            $table->dropColumn(['denda', 'hari_telat']);
        });
    }
};
