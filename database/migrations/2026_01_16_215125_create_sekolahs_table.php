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
        Schema::create('sekolahs', function (Blueprint $table) {
            $table->id();
            $table->string('npsn', 20)->unique();
            $table->string('nama');
            $table->string('nama_yayasan')->nullable();
            $table->enum('jenjang', ['TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MI', 'MTs', 'RA']);
            $table->enum('status', ['Negeri', 'Swasta'])->default('Swasta');
            $table->text('alamat');
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah', 30)->nullable();
            $table->string('logo')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->year('tahun_berdiri')->nullable();
            $table->string('akreditasi', 5)->nullable();
            $table->date('tanggal_akreditasi')->nullable();
            $table->string('no_sk_operasional')->nullable();
            $table->date('tanggal_sk_operasional')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nama');
            $table->index('jenjang');
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sekolahs');
    }
};
