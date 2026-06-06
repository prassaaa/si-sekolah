<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->decimal('applied_amount', 15, 2)->default(0)->after('jumlah_bayar');
            $table->timestamp('applied_at')->nullable()->after('applied_amount');
        });

        DB::table('pembayarans')
            ->where('status', 'berhasil')
            ->whereNull('deleted_at')
            ->update([
                'applied_amount' => DB::raw('jumlah_bayar'),
                'applied_at' => DB::raw('updated_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropColumn(['applied_amount', 'applied_at']);
        });
    }
};
