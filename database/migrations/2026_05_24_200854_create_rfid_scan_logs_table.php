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
        Schema::create('rfid_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 32);
            $table->foreignId('kartu_rfid_id')->nullable()->constrained('kartu_rfids')->nullOnDelete();
            $table->nullableMorphs('owner');
            $table->foreignId('rfid_device_id')->nullable()->constrained('rfid_devices')->nullOnDelete();
            $table->enum('jenis', ['masuk', 'pulang', 'duplikat', 'ditolak', 'tidak_dikenal']);
            $table->string('pesan', 255);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->dateTime('scanned_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('scanned_at');
            $table->index(['owner_type', 'owner_id', 'scanned_at']);
            $table->index(['uid', 'scanned_at']);
            $table->index(['rfid_device_id', 'scanned_at']);
            $table->index(['jenis', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_scan_logs');
    }
};
