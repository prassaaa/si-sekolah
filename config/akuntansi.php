<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tanggal cut-off posting jurnal otomatis
    |--------------------------------------------------------------------------
    |
    | Poster jurnal otomatis (pembayaran SPP, tabungan, gaji, sarpras) HANYA
    | memposting transaksi yang tanggalnya >= cut-off ini. Transaksi sebelum
    | cut-off dianggap era pra-pembukuan otomatis dan tidak dijurnal (posisinya
    | sudah diwakili oleh saldo awal). Keputusan bisnis: mulai TA 2026/2027.
    |
    */

    'cutoff_posting' => env('ACCOUNTING_CUTOFF_POSTING', '2026-07-01'),

    /*
    |--------------------------------------------------------------------------
    | Kode akun default (COA) untuk posting otomatis
    |--------------------------------------------------------------------------
    |
    | Resolusi: cari Akun berdasarkan kode; bila tidak ditemukan, poster
    | melewati posting dengan Log::warning (mengikuti pola AkunResolver /
    | KasJournalPoster) — tidak pernah menebak akun.
    |
    */

    'akun' => [
        'kas_default' => env('ACCOUNTING_AKUN_KAS', '1-1001'),
        'pendapatan_spp_default' => '4-1001',
        'titipan_tabungan' => '2-1004',
        'hutang_gaji' => '2-1002',
        'hutang_pajak' => '2-1003',
        'beban_gaji_guru' => '5-1001',
        'beban_gaji_karyawan' => '5-1002',
        'beban_pemeliharaan' => '5-3003',
        'kerugian_penghapusan_aset' => '5-5002',
        'pendapatan_denda' => '4-1006',
        'aset_tetap' => '1-4001',
        'akumulasi_penyusutan' => '1-4002',
    ],

];
