<?php

use App\Http\Controllers\BukuPribadiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/auth/login');
});

Route::get('/siswa/{siswa}/buku-pribadi', BukuPribadiController::class)
    ->name('siswa.buku-pribadi');
