<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 50px 42px; }
        body { font-family: 'DejaVu Serif', serif; font-size: 10px; color: #111; line-height: 1.45; }

        table.kop { width: 100%; border-collapse: collapse; }
        td.kop-logo { width: 70px; vertical-align: middle; text-align: left; }
        td.kop-logo img { width: 62px; height: 62px; }
        td.kop-spacer { width: 70px; }
        td.kop-teks { vertical-align: middle; text-align: center; }
        .kop-yayasan { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; }
        .kop-nama { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 1px 0; }
        .kop-alamat { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #222; }
        .kop-line-tebal { border-top: 3px solid #000; margin-top: 6px; }
        .kop-line-tipis { border-top: 1px solid #000; margin-top: 1.5px; margin-bottom: 14px; }

        .judul { text-align: center; margin-bottom: 12px; }
        .judul-utama { display: inline-block; font-size: 13px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; border-bottom: 2px solid #000; padding: 0 12px 2px; }
        .judul-periode { font-size: 9.5px; margin-top: 3px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #e3e3e3; font-family: 'DejaVu Sans', sans-serif; font-weight: bold; font-size: 8.5px; text-transform: uppercase; padding: 4px 5px; border: 1px solid #555; text-align: center; }
        table.data td { padding: 3px 5px; border: 1px solid #888; vertical-align: top; font-size: 9px; }
        table.data tr.ringkasan td { font-weight: bold; background: #ececec; border: 1px solid #555; }
        .align-left { text-align: left; }
        .align-center { text-align: center; }
        .align-right { text-align: right; }
        .kosong { font-style: italic; color: #555; text-align: center; padding: 8px; }

        .catatan { margin-top: 10px; font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #444; font-style: italic; }

        table.ttd { width: 100%; border-collapse: collapse; margin-top: 28px; page-break-inside: avoid; }
        table.ttd td { width: 50%; text-align: center; vertical-align: top; font-size: 9.5px; }
        .ttd-jarak { height: 52px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; }

        .footer-note { position: fixed; bottom: -30px; left: 0; right: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 7px; color: #888; text-align: center; font-style: italic; }
    </style>
</head>
<body>

    @php
        $logoPath = $sekolah?->logo
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($sekolah->logo)
            : null;
        $showLogo = $logoPath && file_exists($logoPath);

        $alamat1 = implode(', ', array_filter([
            $sekolah?->alamat,
            $sekolah?->kecamatan ? 'Kec. '.$sekolah->kecamatan : null,
        ]));
        $alamat2 = implode(', ', array_filter([
            $sekolah?->kabupaten,
            $sekolah?->provinsi,
            $sekolah?->kode_pos,
        ]));
        $kontak = implode(' • ', array_filter([
            $sekolah?->telepon ? 'Telp. '.$sekolah->telepon : null,
            $sekolah?->email,
            $sekolah?->npsn ? 'NPSN: '.$sekolah->npsn : null,
        ]));
        $colCount = max(1, count($kolom));
    @endphp

    {{-- KOP --}}
    <table class="kop">
        <tr>
            @if($showLogo)
                <td class="kop-logo"><img src="{{ $logoPath }}" alt="Logo"></td>
            @endif
            <td class="kop-teks">
                @if($sekolah?->nama_yayasan)
                    <div class="kop-yayasan">{{ $sekolah->nama_yayasan }}</div>
                @endif
                <div class="kop-nama">{{ $sekolah?->nama ?? 'NAMA SEKOLAH' }}</div>
                @if($alamat1)<div class="kop-alamat">{{ $alamat1 }}</div>@endif
                @if($alamat2)<div class="kop-alamat">{{ $alamat2 }}</div>@endif
                @if($kontak)<div class="kop-alamat">{{ $kontak }}</div>@endif
            </td>
            @if($showLogo)<td class="kop-spacer"></td>@endif
        </tr>
    </table>
    <div class="kop-line-tebal"></div>
    <div class="kop-line-tipis"></div>

    {{-- JUDUL --}}
    <div class="judul">
        <span class="judul-utama">{{ $judul }}</span>
        @if($periode)<div class="judul-periode">{{ $periode }}</div>@endif
    </div>

    {{-- TABEL --}}
    <table class="data">
        <thead>
            <tr>
                @foreach($kolom as $k)
                    <th class="align-{{ $k['align'] }}">{{ $k['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($baris as $row)
                <tr>
                    @foreach($kolom as $i => $k)
                        <td class="align-{{ $k['align'] }}">{{ $row[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="kosong" colspan="{{ $colCount }}">Tidak ada data untuk ditampilkan.</td></tr>
            @endforelse

            @foreach($ringkasan as $row)
                <tr class="ringkasan">
                    @foreach($kolom as $i => $k)
                        <td class="align-{{ $k['align'] }}">{{ $row[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($catatan)
        <div class="catatan">{{ $catatan }}</div>
    @endif

    {{-- TTD --}}
    @if($withTtd)
        <table class="ttd">
            <tr>
                <td>
                    <div>Bendahara,</div>
                    <div class="ttd-jarak"></div>
                    <div class="ttd-nama">( ........................................ )</div>
                </td>
                <td>
                    <div>{{ $sekolah?->kabupaten ?? '..................' }}, {{ now()->translatedFormat('d F Y') }}</div>
                    <div>Mengetahui, Kepala Sekolah</div>
                    <div class="ttd-jarak"></div>
                    <div class="ttd-nama">{{ $sekolah?->kepala_sekolah ?? '........................................' }}</div>
                    <div>NIP. {{ $sekolah?->nip_kepala_sekolah ?? '-' }}</div>
                </td>
            </tr>
        </table>
    @endif

    <div class="footer-note">
        Dicetak dari Sistem Informasi {{ $sekolah?->nama ?? 'Sekolah' }} — {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

</body>
</html>
