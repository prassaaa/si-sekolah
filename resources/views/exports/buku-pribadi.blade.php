<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Pribadi Siswa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.4;
        }
        .kop {
            width: 100%;
            margin-bottom: 8px;
        }
        .kop-inner {
            width: 100%;
        }
        .kop-logo {
            width: 60px;
            vertical-align: middle;
        }
        .kop-logo img {
            width: 55px;
            height: 55px;
        }
        .kop-teks {
            vertical-align: middle;
            text-align: center;
        }
        .kop-nama {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-alamat {
            font-size: 9px;
            margin-top: 2px;
        }
        .kop-border {
            border-top: 3px double #000;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .judul {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        h2 {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
            margin-top: 10px;
            background: #e8e8e8;
            padding: 3px 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.identitas td {
            padding: 2px 4px;
            vertical-align: top;
        }
        table.identitas td:first-child {
            width: 30%;
            color: #333;
        }
        table.identitas td:nth-child(2) {
            width: 3%;
        }
        table.data-table th {
            background: #ddd;
            font-weight: bold;
            padding: 3px 4px;
            border: 1px solid #aaa;
            text-align: center;
            font-size: 9px;
        }
        table.data-table td {
            padding: 2px 4px;
            border: 1px solid #ccc;
            vertical-align: top;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) td {
            background: #f9f9f9;
        }
        .total-row td {
            font-weight: bold;
            background: #efefef !important;
            border: 1px solid #aaa;
        }
        .kosong {
            color: #666;
            font-style: italic;
            padding: 4px;
        }
        .ttd-block {
            margin-top: 30px;
            text-align: right;
        }
        .ttd-block .nama-kepsek {
            font-weight: bold;
            text-decoration: underline;
        }
        .presensi-label {
            text-transform: capitalize;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <table class="kop">
        <tr class="kop-inner">
            @php
                $logoPath = $sekolah?->logo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->path($sekolah->logo)
                    : null;
                $showLogo = $logoPath && file_exists($logoPath);
            @endphp
            @if($showLogo)
            <td class="kop-logo">
                <img src="{{ $logoPath }}" alt="Logo Sekolah">
            </td>
            @endif
            <td class="kop-teks">
                <div class="kop-nama">{{ $sekolah?->nama ?? 'NAMA SEKOLAH' }}</div>
                <div class="kop-alamat">
                    {{ implode(', ', array_filter([
                        $sekolah?->alamat,
                        $sekolah?->kecamatan,
                        $sekolah?->kabupaten,
                    ])) }}
                </div>
                @if($sekolah?->telepon || $sekolah?->email)
                <div class="kop-alamat">
                    {{ implode(' | ', array_filter([
                        $sekolah?->telepon ? 'Telp. '.$sekolah->telepon : null,
                        $sekolah?->email,
                    ])) }}
                </div>
                @endif
                @if($sekolah?->npsn)
                <div class="kop-alamat">NPSN: {{ $sekolah->npsn }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="kop-border"></div>

    {{-- JUDUL --}}
    <div class="judul">Buku Pribadi Siswa</div>

    {{-- A. IDENTITAS SISWA --}}
    <h2>A. Identitas Siswa</h2>
    <table class="identitas">
        <tr>
            <td>NIS</td><td>:</td><td>{{ $siswa->nis ?? '-' }}</td>
            <td>NISN</td><td>:</td><td>{{ $siswa->nisn ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nama Lengkap</td><td>:</td><td colspan="3">{{ $siswa->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td>Jenis Kelamin</td><td>:</td><td>{{ $siswa->jenis_kelamin_label ?? '-' }}</td>
            <td>Agama</td><td>:</td><td>{{ $siswa->agama ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tempat, Tgl Lahir</td><td>:</td><td colspan="3">{{ $siswa->ttl ?? '-' }}</td>
        </tr>
        <tr>
            <td>Alamat</td><td>:</td><td colspan="3">{{ $siswa->alamat_lengkap ?? '-' }}</td>
        </tr>
        <tr>
            <td>Kelas</td><td>:</td><td>{{ $siswa->kelas?->nama ?? '-' }}</td>
            <td>Tahun Masuk</td><td>:</td><td>{{ $siswa->tahun_masuk ?? '-' }}</td>
        </tr>
        <tr>
            <td>Status</td><td>:</td><td colspan="3">{{ $siswa->status_info['label'] ?? '-' }}</td>
        </tr>
    </table>

    {{-- Ringkasan Orang Tua / Wali --}}
    <table class="data-table" style="margin-top:4px;">
        <tr>
            <th>Hubungan</th>
            <th>Nama</th>
            <th>Pekerjaan</th>
            <th>Telepon</th>
        </tr>
        @if($siswa->nama_ayah)
        <tr>
            <td>Ayah</td>
            <td>{{ $siswa->nama_ayah }}</td>
            <td>{{ $siswa->pekerjaan_ayah ?? '-' }}</td>
            <td>{{ $siswa->telepon_ayah ?? '-' }}</td>
        </tr>
        @endif
        @if($siswa->nama_ibu)
        <tr>
            <td>Ibu</td>
            <td>{{ $siswa->nama_ibu }}</td>
            <td>{{ $siswa->pekerjaan_ibu ?? '-' }}</td>
            <td>{{ $siswa->telepon_ibu ?? '-' }}</td>
        </tr>
        @endif
        @if($siswa->nama_wali)
        <tr>
            <td>Wali ({{ $siswa->hubungan_wali ?? '-' }})</td>
            <td>{{ $siswa->nama_wali }}</td>
            <td>{{ $siswa->pekerjaan_wali ?? '-' }}</td>
            <td>{{ $siswa->telepon_wali ?? '-' }}</td>
        </tr>
        @endif
        @if(!$siswa->nama_ayah && !$siswa->nama_ibu && !$siswa->nama_wali)
        <tr>
            <td colspan="4" class="kosong">Data orang tua/wali belum diisi.</td>
        </tr>
        @endif
    </table>

    {{-- B. REKAP PRESENSI --}}
    <h2>B. Rekap Presensi</h2>
    <table class="data-table">
        <tr>
            <th>Status</th>
            <th>Jumlah Hari</th>
        </tr>
        @foreach(['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'] as $key => $label)
        <tr>
            <td>{{ $label }}</td>
            <td style="text-align:center;">{{ $presensi_rekap[$key] ?? 0 }}</td>
        </tr>
        @endforeach
        @php
            $totalPresensi = array_sum($presensi_rekap);
        @endphp
        <tr class="total-row">
            <td>Total</td>
            <td style="text-align:center;">{{ $totalPresensi }}</td>
        </tr>
    </table>

    {{-- C. RIWAYAT PELANGGARAN --}}
    <h2>C. Riwayat Pelanggaran</h2>
    @if($pelanggarans->isEmpty())
        <p class="kosong">Tidak ada catatan pelanggaran.</p>
    @else
        <table class="data-table">
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:12%;">Tanggal</th>
                <th>Jenis Pelanggaran</th>
                <th style="width:12%;">Kategori</th>
                <th style="width:6%;">Poin</th>
                <th style="width:10%;">Status</th>
            </tr>
            @foreach($pelanggarans as $i => $pelanggaran)
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $pelanggaran->tanggal?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $pelanggaran->jenis_pelanggaran ?? '-' }}</td>
                <td>{{ $pelanggaran->kategori_info['label'] ?? '-' }}</td>
                <td style="text-align:center;">{{ $pelanggaran->poin ?? 0 }}</td>
                <td>{{ $pelanggaran->status_info['label'] ?? '-' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total Poin</td>
                <td style="text-align:center;">{{ $total_poin }}</td>
                <td></td>
            </tr>
        </table>
    @endif

    {{-- D. RIWAYAT KONSELING --}}
    <h2>D. Riwayat Konseling</h2>
    @if($konselings->isEmpty())
        <p class="kosong">Tidak ada catatan konseling.</p>
    @else
        <table class="data-table">
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:12%;">Tanggal</th>
                <th style="width:14%;">Jenis</th>
                <th style="width:14%;">Kategori</th>
                <th>Konselor</th>
                <th style="width:12%;">Status</th>
            </tr>
            @foreach($konselings as $i => $konseling)
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $konseling->tanggal?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $konseling->jenis ?? '-' }}</td>
                <td>{{ $konseling->kategori_info['label'] ?? '-' }}</td>
                <td>{{ $konseling->konselor?->nama ?? '-' }}</td>
                <td>{{ $konseling->status_info['label'] ?? '-' }}</td>
            </tr>
            @endforeach
        </table>
    @endif

    {{-- E. PRESTASI --}}
    <h2>E. Prestasi</h2>
    @if($prestasis->isEmpty())
        <p class="kosong">Belum ada data prestasi.</p>
    @else
        <table class="data-table">
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:12%;">Tanggal</th>
                <th>Nama Prestasi</th>
                <th style="width:14%;">Tingkat</th>
                <th style="width:10%;">Peringkat</th>
            </tr>
            @foreach($prestasis as $i => $prestasi)
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $prestasi->tanggal?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $prestasi->nama_prestasi ?? '-' }}</td>
                <td>{{ $prestasi->tingkat ?? '-' }}</td>
                <td style="text-align:center;">{{ $prestasi->peringkat ?? '-' }}</td>
            </tr>
            @endforeach
        </table>
    @endif

    {{-- F. TAHFIDZ --}}
    <h2>F. Tahfidz</h2>
    @if($tahfidzs->isEmpty())
        <p class="kosong">Belum ada data tahfidz.</p>
    @else
        <table class="data-table">
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:12%;">Tanggal</th>
                <th>Surah</th>
                <th style="width:14%;">Ayat</th>
                <th style="width:6%;">Juz</th>
                <th style="width:6%;">Nilai</th>
                <th style="width:10%;">Status</th>
            </tr>
            @foreach($tahfidzs as $i => $tahfidz)
            <tr>
                <td style="text-align:center;">{{ $i + 1 }}</td>
                <td>{{ $tahfidz->tanggal?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $tahfidz->surah ?? '-' }}</td>
                <td style="text-align:center;">
                    {{ $tahfidz->ayat_mulai ?? '-' }} &ndash; {{ $tahfidz->ayat_selesai ?? '-' }}
                </td>
                <td style="text-align:center;">{{ $tahfidz->juz ?? '-' }}</td>
                <td style="text-align:center;">{{ $tahfidz->nilai ?? '-' }}</td>
                <td>{{ $tahfidz->status_info['label'] ?? '-' }}</td>
            </tr>
            @endforeach
        </table>
    @endif

    {{-- TANDA TANGAN --}}
    <div class="ttd-block">
        <p>
            {{ $sekolah?->kabupaten ?? 'Kabupaten' }},
            {{ now()->translatedFormat('d F Y') }}
        </p>
        <p style="margin-top:4px;">Kepala Sekolah,</p>
        <p style="margin-top:40px;" class="nama-kepsek">{{ $sekolah?->kepala_sekolah ?? '___________________' }}</p>
        <p>NIP. {{ $sekolah?->nip_kepala_sekolah ?? '-' }}</p>
    </div>

</body>
</html>
