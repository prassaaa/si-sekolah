<?php

use App\Services\Wa\LogWaGateway;

return [
    /*
    |--------------------------------------------------------------------------
    | Driver WhatsApp Aktif
    |--------------------------------------------------------------------------
    |
    | Driver yang digunakan untuk mengirim pesan WhatsApp. Nilai default 'log'
    | hanya mencatat ke log aplikasi tanpa mengirim pesan nyata.
    | Untuk produksi, ganti ke 'fonnte' atau 'wablas' lalu isi konfigurasi
    | di bagian 'drivers' di bawah.
    |
    */
    'driver' => env('WA_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Nomor Pengirim
    |--------------------------------------------------------------------------
    |
    | Nomor WhatsApp pengirim (format internasional, tanpa +). Dipakai oleh
    | driver berbayar seperti Fonnte atau Wablas.
    |
    */
    'sender' => env('WA_SENDER', ''),

    /*
    |--------------------------------------------------------------------------
    | Daftar Driver
    |--------------------------------------------------------------------------
    |
    | Pemetaan nama driver ke kelas implementasi. Tambahkan driver baru di sini
    | tanpa mengubah kode aplikasi.
    |
    */
    'drivers' => [
        'log' => LogWaGateway::class,
        // 'fonnte' => \App\Services\Wa\FonnteWaGateway::class,
        // 'wablas' => \App\Services\Wa\WablasWaGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Driver Berbayar
    |--------------------------------------------------------------------------
    */
    'fonnte' => [
        'token' => env('FONNTE_TOKEN', ''),
    ],

    'wablas' => [
        'token' => env('WABLAS_TOKEN', ''),
        'base_url' => env('WABLAS_URL', 'https://solo.wablas.com'),
    ],
];
