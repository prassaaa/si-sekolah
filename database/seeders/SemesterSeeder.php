<?php

namespace Database\Seeders;

use App\Models\Semester;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjarans = TahunAjaran::all();

        foreach ($tahunAjarans as $tahunAjaran) {
            // Semester Ganjil
            Semester::firstOrCreate(
                [
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'semester' => 1,
                ],
                [
                    'nama' => 'Semester Ganjil '.$tahunAjaran->kode,
                    'tanggal_mulai' => $tahunAjaran->tanggal_mulai,
                    'tanggal_selesai' => $tahunAjaran->tanggal_mulai->copy()->addMonths(5)->endOfMonth(),
                    'is_active' => $tahunAjaran->is_active && now()->month >= 7,
                ]
            );

            // Semester Genap
            Semester::firstOrCreate(
                [
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'semester' => 2,
                ],
                [
                    'nama' => 'Semester Genap '.$tahunAjaran->kode,
                    'tanggal_mulai' => $tahunAjaran->tanggal_mulai->copy()->addMonths(6)->startOfMonth(),
                    'tanggal_selesai' => $tahunAjaran->tanggal_selesai,
                    'is_active' => $tahunAjaran->is_active && now()->month < 7,
                ]
            );
        }
    }
}
