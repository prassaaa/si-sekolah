<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Services\Kesiswaan\BukuPribadiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BukuPribadiController extends Controller
{
    public function __construct(private readonly BukuPribadiService $bukuPribadiService) {}

    /**
     * Tampilkan pratinjau PDF buku pribadi siswa di browser (inline).
     */
    public function __invoke(Request $request, Siswa $siswa): Response
    {
        abort_unless($request->user()?->can('View:Siswa') ?? false, 403);

        return $this->bukuPribadiService->pdf($siswa)
            ->stream($this->bukuPribadiService->filename($siswa));
    }
}
