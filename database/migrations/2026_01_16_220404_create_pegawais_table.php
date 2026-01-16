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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique()->nullable();
            $table->string('nuptk', 30)->unique()->nullable();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'])->default('Islam');
            $table->text('alamat')->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('foto')->nullable();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan_pegawais')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status_kepegawaian', ['PNS', 'PPPK', 'GTY', 'GTT', 'PTY', 'PTT'])->default('GTT');
            $table->enum('pendidikan_terakhir', ['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])->nullable();
            $table->string('jurusan')->nullable();
            $table->string('universitas')->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();
            $table->string('no_rekening', 30)->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('no_bpjs_kesehatan', 30)->nullable();
            $table->string('no_bpjs_ketenagakerjaan', 30)->nullable();
            $table->enum('status_pernikahan', ['Belum Menikah', 'Menikah', 'Cerai'])->default('Belum Menikah');
            $table->tinyInteger('jumlah_tanggungan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nama');
            $table->index('jenis_kelamin');
            $table->index('jabatan_id');
            $table->index('user_id');
            $table->index('status_kepegawaian');
            $table->index('tanggal_masuk');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
