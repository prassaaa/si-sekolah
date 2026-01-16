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
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();

            // Identitas Utama
            $table->string('nis', 20)->unique()->comment('Nomor Induk Siswa');
            $table->string('nisn', 20)->nullable()->unique()->comment('Nomor Induk Siswa Nasional');
            $table->string('nama', 100);
            $table->string('nama_panggilan', 50)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nik', 20)->nullable()->comment('NIK KTP');
            $table->string('no_kk', 20)->nullable()->comment('Nomor Kartu Keluarga');
            $table->string('no_akta', 30)->nullable()->comment('Nomor Akta Kelahiran');
            $table->string('agama', 20)->nullable();
            $table->string('kewarganegaraan', 50)->nullable()->default('Indonesia');
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->unsignedTinyInteger('jumlah_saudara')->nullable();

            // Alamat
            $table->text('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('kelurahan', 50)->nullable();
            $table->string('kecamatan', 50)->nullable();
            $table->string('kota', 50)->nullable();
            $table->string('provinsi', 50)->nullable();
            $table->string('kode_pos', 10)->nullable();

            // Kontak
            $table->string('telepon', 20)->nullable();
            $table->string('hp', 20)->nullable();
            $table->string('email', 100)->nullable();

            // Data Akademik
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->date('tanggal_masuk')->nullable();
            $table->string('asal_sekolah', 100)->nullable();
            $table->enum('status', ['aktif', 'lulus', 'pindah', 'dikeluarkan', 'dropout', 'tidak_aktif'])->default('aktif');
            $table->year('tahun_masuk')->nullable();

            // Data Kesehatan
            $table->string('golongan_darah', 5)->nullable();
            $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('dalam cm');
            $table->decimal('berat_badan', 5, 2)->nullable()->comment('dalam kg');
            $table->text('riwayat_penyakit')->nullable();

            // Data Orang Tua - Ayah
            $table->string('nama_ayah', 100)->nullable();
            $table->string('nik_ayah', 20)->nullable();
            $table->string('tempat_lahir_ayah', 50)->nullable();
            $table->date('tanggal_lahir_ayah')->nullable();
            $table->string('pendidikan_ayah', 30)->nullable();
            $table->string('pekerjaan_ayah', 50)->nullable();
            $table->decimal('penghasilan_ayah', 15, 2)->nullable();
            $table->string('telepon_ayah', 20)->nullable();
            $table->text('alamat_ayah')->nullable();

            // Data Orang Tua - Ibu
            $table->string('nama_ibu', 100)->nullable();
            $table->string('nik_ibu', 20)->nullable();
            $table->string('tempat_lahir_ibu', 50)->nullable();
            $table->date('tanggal_lahir_ibu')->nullable();
            $table->string('pendidikan_ibu', 30)->nullable();
            $table->string('pekerjaan_ibu', 50)->nullable();
            $table->decimal('penghasilan_ibu', 15, 2)->nullable();
            $table->string('telepon_ibu', 20)->nullable();
            $table->text('alamat_ibu')->nullable();

            // Data Wali
            $table->string('nama_wali', 100)->nullable();
            $table->string('nik_wali', 20)->nullable();
            $table->string('hubungan_wali', 30)->nullable();
            $table->string('tempat_lahir_wali', 50)->nullable();
            $table->date('tanggal_lahir_wali')->nullable();
            $table->string('pendidikan_wali', 30)->nullable();
            $table->string('pekerjaan_wali', 50)->nullable();
            $table->decimal('penghasilan_wali', 15, 2)->nullable();
            $table->string('telepon_wali', 20)->nullable();
            $table->text('alamat_wali')->nullable();

            // Dokumen
            $table->string('foto', 255)->nullable();
            $table->string('foto_kk', 255)->nullable();
            $table->string('foto_akta', 255)->nullable();
            $table->string('foto_ijazah', 255)->nullable();

            // Keterangan
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('nama');
            $table->index('jenis_kelamin');
            $table->index('status');
            $table->index('kelas_id');
            $table->index('is_active');
            $table->index('tahun_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
