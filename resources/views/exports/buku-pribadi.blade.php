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
        @page {
            margin: 56px 48px;
        }
        body {
            font-family: 'DejaVu Serif', serif;
            font-size: 10px;
            color: #111;
            line-height: 1.5;
        }

        /* ===== KOP SURAT ===== */
        table.kop {
            width: 100%;
            border-collapse: collapse;
        }
        td.kop-logo {
            width: 78px;
            vertical-align: middle;
            text-align: left;
        }
        td.kop-logo img {
            width: 68px;
            height: 68px;
        }
        td.kop-spacer {
            width: 78px;
        }
        td.kop-teks {
            vertical-align: middle;
            text-align: center;
        }
        .kop-yayasan {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .kop-nama {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1px 0;
        }
        .kop-alamat {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #222;
        }
        .kop-line-tebal {
            border-top: 3px solid #000;
            margin-top: 7px;
        }
        .kop-line-tipis {
            border-top: 1px solid #000;
            margin-top: 1.5px;
            margin-bottom: 16px;
        }

        /* ===== JUDUL DOKUMEN ===== */
        .judul {
            text-align: center;
            margin-bottom: 14px;
        }
        .judul-utama {
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding: 0 14px 2px;
        }
        .judul-sub {
            font-size: 10px;
            margin-top: 4px;
        }

        /* ===== SECTION ===== */
        .section {
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #ececec;
            border: 1px solid #444;
            border-left: 5px solid #000;
            padding: 3px 8px;
            margin-bottom: 6px;
        }

        /* ===== IDENTITAS ===== */
        table.identitas-wrap {
            width: 100%;
            border-collapse: collapse;
        }
        td.identitas-kiri {
            vertical-align: top;
        }
        td.identitas-foto {
            width: 100px;
            vertical-align: top;
            text-align: center;
        }
        .foto-box {
            width: 85px;
            height: 113px;
            border: 1px solid #555;
            margin-left: 12px;
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #777;
        }
        .foto-box img {
            width: 85px;
            height: 113px;
            object-fit: cover;
        }
        .foto-keterangan {
            padding-top: 48px;
            display: block;
        }
        table.identitas td {
            padding: 1.5px 4px;
            vertical-align: top;
            font-size: 10px;
        }
        table.identitas td.no {
            width: 4%;
        }
        table.identitas td.label {
            width: 33%;
        }
        table.identitas td.titik {
            width: 3%;
        }

        /* ===== TABEL DATA ===== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        table.data-table th {
            font-family: 'DejaVu Sans', sans-serif;
            background: #e3e3e3;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 4px;
            border: 1px solid #555;
            text-align: center;
        }
        table.data-table td {
            padding: 3px 5px;
            border: 1px solid #777;
            vertical-align: top;
            font-size: 9.5px;
        }
        .total-row td {
            font-weight: bold;
            background: #ececec;
            border: 1px solid #555;
        }
        .kosong {
            font-style: italic;
            color: #444;
            border: 1px solid #777;
            padding: 5px 8px;
            font-size: 9.5px;
        }
        .ket-baik {
            font-weight: bold;
        }

        /* ===== TANDA TANGAN ===== */
        table.ttd {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
            page-break-inside: avoid;
        }
        table.ttd td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
        }
        .ttd-jarak {
            height: 58px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }

        /* ===== FOOTER ===== */
        .footer-note {
            margin-top: 22px;
            border-top: 0.5px solid #999;
            padding-top: 3px;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7px;
            color: #666;
            font-style: italic;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        $logoPath = $sekolah?->logo
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($sekolah->logo)
            : null;
        $showLogo = $logoPath && file_exists($logoPath);

        $fotoPath = $siswa->foto
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($siswa->foto)
            : null;
        $showFoto = $fotoPath && file_exists($fotoPath);

        $alamatBaris1 = implode(', ', array_filter([
            $sekolah?->alamat,
            $sekolah?->kelurahan ? 'Kel. '.$sekolah->kelurahan : null,
            $sekolah?->kecamatan ? 'Kec. '.$sekolah->kecamatan : null,
        ]));
        $alamatBaris2 = implode(', ', array_filter([
            $sekolah?->kabupaten,
            $sekolah?->provinsi,
            $sekolah?->kode_pos,
        ]));
        $kontak = implode(' &bull; ', array_filter([
            $sekolah?->telepon ? 'Telp. '.$sekolah->telepon : null,
            $sekolah?->email ? 'Email: '.$sekolah->email : null,
            $sekolah?->website,
        ]));
        $legalitas = implode(' &bull; ', array_filter([
            $sekolah?->npsn ? 'NPSN: '.$sekolah->npsn : null,
            $sekolah?->akreditasi ? 'Terakreditasi "'.$sekolah->akreditasi.'"' : null,
        ]));
    @endphp

    {{-- ===== KOP SURAT ===== --}}
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
                @if($alamatBaris1)
                    <div class="kop-alamat">{{ $alamatBaris1 }}</div>
                @endif
                @if($alamatBaris2)
                    <div class="kop-alamat">{{ $alamatBaris2 }}</div>
                @endif
                @if($kontak)
                    <div class="kop-alamat">{!! $kontak !!}</div>
                @endif
                @if($legalitas)
                    <div class="kop-alamat">{!! $legalitas !!}</div>
                @endif
            </td>
            @if($showLogo)
                <td class="kop-spacer"></td>
            @endif
        </tr>
    </table>
    <div class="kop-line-tebal"></div>
    <div class="kop-line-tipis"></div>

    {{-- ===== JUDUL ===== --}}
    <div class="judul">
        <span class="judul-utama">Buku Pribadi Siswa</span>
        @if($tahun_ajaran)
            <div class="judul-sub">Tahun Pelajaran {{ $tahun_ajaran->kode ?? $tahun_ajaran->nama }}</div>
        @endif
    </div>

    {{-- ===== A. IDENTITAS SISWA ===== --}}
    <div class="section">
        <div class="section-title">A. Identitas Siswa</div>
        <table class="identitas-wrap">
            <tr>
                <td class="identitas-kiri">
                    <table class="identitas">
                        <tr><td class="no">1.</td><td class="label">Nama Lengkap</td><td class="titik">:</td><td><strong>{{ $siswa->nama ?? '-' }}</strong></td></tr>
                        <tr><td class="no">2.</td><td class="label">NIS / NISN</td><td class="titik">:</td><td>{{ $siswa->nis ?? '-' }} / {{ $siswa->nisn ?? '-' }}</td></tr>
                        <tr><td class="no">3.</td><td class="label">Jenis Kelamin</td><td class="titik">:</td><td>{{ $siswa->jenis_kelamin_label ?? '-' }}</td></tr>
                        <tr><td class="no">4.</td><td class="label">Tempat, Tanggal Lahir</td><td class="titik">:</td><td>{{ $siswa->ttl ?? '-' }}</td></tr>
                        <tr><td class="no">5.</td><td class="label">Agama</td><td class="titik">:</td><td>{{ $siswa->agama ?? '-' }}</td></tr>
                        <tr><td class="no">6.</td><td class="label">Alamat</td><td class="titik">:</td><td>{{ $siswa->alamat_lengkap ?? '-' }}</td></tr>
                        <tr><td class="no">7.</td><td class="label">Kelas</td><td class="titik">:</td><td>{{ $siswa->kelas?->nama ?? '-' }}</td></tr>
                        <tr><td class="no">8.</td><td class="label">Tahun Masuk</td><td class="titik">:</td><td>{{ $siswa->tahun_masuk ?? '-' }}</td></tr>
                        <tr><td class="no">9.</td><td class="label">Asal Sekolah</td><td class="titik">:</td><td>{{ $siswa->asal_sekolah ?? '-' }}</td></tr>
                        <tr><td class="no">10.</td><td class="label">Status Siswa</td><td class="titik">:</td><td>{{ $siswa->status_info['label'] ?? '-' }}</td></tr>
                    </table>
                </td>
                <td class="identitas-foto">
                    <div class="foto-box">
                        @if($showFoto)
                            <img src="{{ $fotoPath }}" alt="Foto Siswa">
                        @else
                            <span class="foto-keterangan">Pas Foto<br>3 &times; 4</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== B. ORANG TUA / WALI ===== --}}
    <div class="section">
        <div class="section-title">B. Data Orang Tua / Wali</div>
        @if($siswa->nama_ayah || $siswa->nama_ibu || $siswa->nama_wali)
            <table class="data-table">
                <tr>
                    <th style="width:16%;">Hubungan</th>
                    <th>Nama</th>
                    <th style="width:24%;">Pekerjaan</th>
                    <th style="width:18%;">Telepon</th>
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
            </table>
        @else
            <div class="kosong">Data orang tua/wali belum diisi.</div>
        @endif
    </div>

    {{-- ===== C. REKAP PRESENSI ===== --}}
    <div class="section">
        <div class="section-title">C. Rekapitulasi Presensi</div>
        <table class="data-table">
            <tr>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Izin</th>
                <th>Sakit</th>
                <th>Alpha</th>
                <th>Jumlah</th>
            </tr>
            <tr>
                <td style="text-align:center;">{{ $presensi_rekap['hadir'] ?? 0 }}</td>
                <td style="text-align:center;">{{ $presensi_rekap['terlambat'] ?? 0 }}</td>
                <td style="text-align:center;">{{ $presensi_rekap['izin'] ?? 0 }}</td>
                <td style="text-align:center;">{{ $presensi_rekap['sakit'] ?? 0 }}</td>
                <td style="text-align:center;">{{ $presensi_rekap['alpha'] ?? 0 }}</td>
                <td style="text-align:center;"><strong>{{ array_sum($presensi_rekap) }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- ===== D. RIWAYAT PELANGGARAN ===== --}}
    <div class="section">
        <div class="section-title">D. Riwayat Pelanggaran / Catatan Kedisiplinan</div>
        @if($pelanggarans->isEmpty())
            <div class="kosong ket-baik">Tidak ada catatan pelanggaran. Siswa yang bersangkutan berkelakuan baik.</div>
        @else
            <table class="data-table">
                <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:12%;">Tanggal</th>
                    <th>Jenis Pelanggaran</th>
                    <th style="width:11%;">Kategori</th>
                    <th style="width:7%;">Poin</th>
                    <th style="width:11%;">Status</th>
                </tr>
                @foreach($pelanggarans as $i => $pelanggaran)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td style="text-align:center;">{{ $pelanggaran->tanggal?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $pelanggaran->jenis_pelanggaran ?? '-' }}</td>
                        <td style="text-align:center;">{{ $pelanggaran->kategori_info['label'] ?? '-' }}</td>
                        <td style="text-align:center;">{{ $pelanggaran->poin ?? 0 }}</td>
                        <td style="text-align:center;">{{ $pelanggaran->status_info['label'] ?? '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Total Poin Pelanggaran</td>
                    <td style="text-align:center;">{{ $total_poin }}</td>
                    <td></td>
                </tr>
            </table>
        @endif
    </div>

    {{-- ===== E. RIWAYAT KONSELING ===== --}}
    <div class="section">
        <div class="section-title">E. Riwayat Bimbingan &amp; Konseling</div>
        @if($konselings->isEmpty())
            <div class="kosong">Tidak ada catatan konseling.</div>
        @else
            <table class="data-table">
                <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:12%;">Tanggal</th>
                    <th style="width:13%;">Jenis</th>
                    <th style="width:13%;">Kategori</th>
                    <th>Konselor</th>
                    <th style="width:12%;">Status</th>
                </tr>
                @foreach($konselings as $i => $konseling)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td style="text-align:center;">{{ $konseling->tanggal?->format('d-m-Y') ?? '-' }}</td>
                        <td style="text-transform:capitalize;">{{ $konseling->jenis ?? '-' }}</td>
                        <td style="text-align:center;">{{ $konseling->kategori_info['label'] ?? '-' }}</td>
                        <td>{{ $konseling->konselor?->nama ?? '-' }}</td>
                        <td style="text-align:center;">{{ $konseling->status_info['label'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- ===== F. PRESTASI ===== --}}
    <div class="section">
        <div class="section-title">F. Prestasi</div>
        @if($prestasis->isEmpty())
            <div class="kosong">Belum ada data prestasi.</div>
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
                        <td style="text-align:center;">{{ $prestasi->tanggal?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $prestasi->nama_prestasi ?? '-' }}</td>
                        <td style="text-align:center; text-transform:capitalize;">{{ $prestasi->tingkat ?? '-' }}</td>
                        <td style="text-align:center;">{{ $prestasi->peringkat ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- ===== G. TAHFIDZ ===== --}}
    <div class="section">
        <div class="section-title">G. Capaian Tahfidz</div>
        @if($tahfidzs->isEmpty())
            <div class="kosong">Belum ada data tahfidz.</div>
        @else
            <table class="data-table">
                <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:12%;">Tanggal</th>
                    <th>Surah</th>
                    <th style="width:13%;">Ayat</th>
                    <th style="width:7%;">Juz</th>
                    <th style="width:8%;">Nilai</th>
                    <th style="width:11%;">Status</th>
                </tr>
                @foreach($tahfidzs as $i => $tahfidz)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td style="text-align:center;">{{ $tahfidz->tanggal?->format('d-m-Y') ?? '-' }}</td>
                        <td>{{ $tahfidz->surah ?? '-' }}</td>
                        <td style="text-align:center;">{{ $tahfidz->ayat_mulai ?? '-' }} &ndash; {{ $tahfidz->ayat_selesai ?? '-' }}</td>
                        <td style="text-align:center;">{{ $tahfidz->juz ?? '-' }}</td>
                        <td style="text-align:center;">{{ $tahfidz->nilai ?? '-' }}</td>
                        <td style="text-align:center;">{{ $tahfidz->status_info['label'] ?? $tahfidz->status ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- ===== TANDA TANGAN ===== --}}
    <table class="ttd">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div>Orang Tua / Wali Murid</div>
                <div class="ttd-jarak"></div>
                <div>( ........................................ )</div>
            </td>
            <td>
                <div>{{ $sekolah?->kabupaten ?? '..................' }}, {{ now()->translatedFormat('d F Y') }}</div>
                <div>Kepala Sekolah,</div>
                <div class="ttd-jarak"></div>
                <div class="ttd-nama">{{ $sekolah?->kepala_sekolah ?? '........................................' }}</div>
                <div>NIP. {{ $sekolah?->nip_kepala_sekolah ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan melalui Sistem Informasi {{ $sekolah?->nama ?? 'Sekolah' }}
        pada {{ now()->translatedFormat('d F Y H:i') }} WIB &mdash; Bagian Bimbingan &amp; Konseling / Kesiswaan.
    </div>

</body>
</html>
