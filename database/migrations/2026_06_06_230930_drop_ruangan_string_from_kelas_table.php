<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kelas', 'ruangan')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropColumn('ruangan');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('kelas', 'ruangan')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->string('ruangan')->nullable()->after('kapasitas');
            });
        }
    }
};
