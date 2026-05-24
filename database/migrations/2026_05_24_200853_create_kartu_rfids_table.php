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
        Schema::create('kartu_rfids', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('uid', 32)->unique();
            $table->enum('status', ['aktif', 'nonaktif', 'hilang', 'rusak'])->default('aktif');
            $table->dateTime('diaktifkan_pada');
            $table->dateTime('dinonaktifkan_pada')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_rfids');
    }
};
