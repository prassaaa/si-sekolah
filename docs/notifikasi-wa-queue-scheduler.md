# Panduan: Notifikasi WhatsApp, Queue Worker & Scheduler

**Status:** Referensi implementasi (belum semua aktif di produksi). Dipakai saat sekolah benar-benar butuh kirim tagihan via WhatsApp otomatis/nyata.
**Konteks kode:** fitur F12 (Wave 5) — lihat `docs/audit-akuntansi-plan.md`.

---

## Ringkasan keadaan sekarang

| Hal | Keadaan default | Catatan |
|---|---|---|
| Kirim WA tagihan | **Manual** — tombol bulk action di halaman *Kirim Tagihan* | Bukan otomatis. Staff pilih siswa → klik "Kirim WA". |
| Driver WA | **`log`** (simulasi) | Hanya menulis ke `storage/logs/laravel.log`, **belum** kirim ke WhatsApp asli. |
| Pengiriman | **Queued** (`ShouldQueue`) | Job masuk antrian tabel `jobs`; butuh **queue worker** agar diproses. |
| Cron terjadwal | Hanya **`sarpras:susut-bulanan`** (penyusutan aset, tiap tgl 1 jam 01:00) | **Tidak ada** jadwal WA. Cron yang Anda lihat = penyusutan, bukan tagihan. |

**Alur saat ini:** *Kirim Tagihan* → centang siswa menunggak → tombol **Kirim WA** → buat baris `notifikasi_tagihans` (status `antri`) + `KirimTagihanWaJob::dispatch()` → worker memproses → driver mengirim → status jadi `terkirim`/`gagal`.

File terkait:
- Halaman: `app/Filament/Pages/KirimTagihan.php` (bulk action `kirimWa`)
- Job: `app/Jobs/KirimTagihanWaJob.php` (`tries=3`, `backoff=60`)
- Kontrak driver: `app/Services/Wa/WaGatewayContract.php`
- Driver default: `app/Services/Wa/LogWaGateway.php`
- Konfigurasi: `config/wa.php`
- Log notifikasi: model `App\Models\NotifikasiTagihan` (kolom `tujuan_nomor`, `pesan`, `status`, `driver`, `response`, `sent_at`)

---

## Bagian 1 — Queue Worker (WAJIB agar WA terkirim)

Job WA `implements ShouldQueue` dan `QUEUE_CONNECTION=database`. Tanpa worker, job hanya menumpuk di tabel `jobs` dan tidak pernah diproses.

### 1a. Pastikan tabel queue ada
```bash
php artisan migrate   # tabel jobs/failed_jobs sudah ada di migrasi 0001_01_01_000002
```

### 1b. Dev / uji cepat
```bash
php artisan queue:work --tries=3
```

### 1c. Produksi — Supervisor (disarankan)
Buat `/etc/supervisor/conf.d/si-sekolah-worker.conf`:
```ini
[program:si-sekolah-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/si-sekolah/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/si-sekolah/storage/logs/worker.log
```
Aktifkan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start si-sekolah-worker:*
```

### 1d. Produksi — systemd (alternatif tanpa supervisor)
`/etc/systemd/system/si-sekolah-worker.service`:
```ini
[Unit]
Description=Si-Sekolah Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/si-sekolah/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl enable --now si-sekolah-worker
```

> **Penting:** setiap kali deploy kode baru, restart worker agar memuat kode terbaru:
> ```bash
> php artisan queue:restart   # worker akan restart sendiri setelah job berjalan selesai
> ```

---

## Bagian 2 — Driver WhatsApp Nyata (Fonnte / Wablas)

Default `log` tidak mengirim apa pun. Untuk kirim nyata: buat 1 kelas driver yang `implements WaGatewayContract`, daftarkan di `config/wa.php`, set `WA_DRIVER` di `.env`. **Tidak perlu mengubah Job, halaman, atau kode lain** — arsitektur sudah pluggable.

### 2a. Contoh driver Fonnte
Buat `app/Services/Wa/FonnteWaGateway.php`:
```php
<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Http;

class FonnteWaGateway implements WaGatewayContract
{
    /**
     * Kirim pesan WhatsApp via API Fonnte.
     *
     * @return array{status: string, response: string}
     */
    public function kirim(string $nomor, string $pesan): array
    {
        $response = Http::withHeaders([
            'Authorization' => (string) config('wa.fonnte.token'),
        ])->asForm()->post('https://api.fonnte.com/send', [
            'target' => $nomor,   // format: 0812xxxx atau 62812xxxx
            'message' => $pesan,
        ]);

        $body = $response->json() ?? [];
        $sukses = $response->successful() && (($body['status'] ?? false) === true);

        return [
            'status' => $sukses ? 'terkirim' : 'gagal',
            'response' => $response->body(),
        ];
    }
}
```

### 2b. Daftarkan di `config/wa.php`
Buka komentar baris yang sudah disiapkan:
```php
'drivers' => [
    'log' => LogWaGateway::class,
    'fonnte' => \App\Services\Wa\FonnteWaGateway::class,   // <- aktifkan
    // 'wablas' => \App\Services\Wa\WablasWaGateway::class,
],
```

### 2c. Isi `.env`
```dotenv
WA_DRIVER=fonnte
WA_SENDER=6281234567890
FONNTE_TOKEN=token-dari-dashboard-fonnte
```
Lalu:
```bash
php artisan config:clear
```

### 2d. Wablas (alternatif)
Pola sama; endpoint `config('wa.wablas.base_url').'/api/send-message'`, header `Authorization: {token}`, body `phone` + `message`. Buat `WablasWaGateway` meniru `FonnteWaGateway`, daftarkan, `WA_DRIVER=wablas`.

> Job memilih driver lewat `config('wa.driver')` (atau kolom `driver` per-notifikasi bila diisi). Kalau driver tak ditemukan, otomatis fallback ke `log` — aman, tidak error.

---

## Bagian 3 — (Opsional) Kirim Tagihan OTOMATIS Terjadwal

Kalau sekolah ingin tagihan terkirim sendiri (mis. tiap tanggal 5 ke semua yang menunggak) tanpa klik manual.

### 3a. Buat command
```bash
php artisan make:command KirimTagihanTerjadwal
```
`app/Console/Commands/KirimTagihanTerjadwal.php` (inti):
```php
public function handle(): int
{
    $tagihans = \App\Models\TagihanSiswa::query()
        ->whereIn('status', ['belum_bayar', 'sebagian'])
        ->where('sisa_tagihan', '>', 0)
        ->whereDate('tanggal_jatuh_tempo', '<=', now())
        ->with('siswa')
        ->get();

    foreach ($tagihans as $tagihan) {
        // IDEMPOTEN: lewati bila sudah dikirim hari ini untuk tagihan ini
        $sudah = \App\Models\NotifikasiTagihan::where('tagihan_siswa_id', $tagihan->id)
            ->whereDate('created_at', now())
            ->exists();
        if ($sudah) {
            continue;
        }

        $nomor = $tagihan->siswa?->hp ?? $tagihan->siswa?->telepon_ayah; // sesuaikan prioritas
        if (! $nomor) {
            continue;
        }

        $notifikasi = \App\Models\NotifikasiTagihan::create([
            'tagihan_siswa_id' => $tagihan->id,
            'siswa_id' => $tagihan->siswa_id,
            'tujuan_nomor' => $nomor,            // sebaiknya format ke 62xxx dulu
            'pesan' => "Yth. Wali {$tagihan->siswa->nama}, tagihan {$tagihan->nomor_tagihan} "
                ."sebesar Rp ".number_format((float) $tagihan->sisa_tagihan, 0, ',', '.')
                ." jatuh tempo. Mohon segera diselesaikan. Terima kasih.",
            'status' => 'antri',
            'driver' => config('wa.driver'),
        ]);

        \App\Jobs\KirimTagihanWaJob::dispatch($notifikasi);
    }

    $this->info($tagihans->count().' tagihan diproses.');

    return self::SUCCESS;
}
```
> Catatan: logika di atas sengaja mirip bulk action manual di `KirimTagihan.php` — bisa diekstrak ke satu service agar tidak dobel.

### 3b. Jadwalkan di `routes/console.php`
Tambah di bawah jadwal penyusutan yang sudah ada:
```php
Schedule::command('sarpras:susut-bulanan')->monthlyOn(1, '01:00');

// Kirim pengingat tagihan tiap tanggal 5 jam 08:00
Schedule::command('tagihan:kirim-terjadwal')->monthlyOn(5, '08:00');
// atau mingguan: ->weeklyOn(1, '08:00');  (Senin)
```

---

## Bagian 4 — Cron Master Laravel Scheduler (FONDASI semua jadwal)

⚠️ **Ini yang sering terlewat.** `Schedule::command(...)` di `routes/console.php` **TIDAK** jalan sendiri. Laravel butuh **satu** entri cron yang memanggil `schedule:run` tiap menit. Tanpa ini, penyusutan bulanan **maupun** kirim tagihan terjadwal **tidak akan pernah jalan**.

Edit crontab server (`crontab -e` sebagai user web):
```cron
* * * * * cd /var/www/si-sekolah && php artisan schedule:run >> /dev/null 2>&1
```

Cek jadwal terdaftar:
```bash
php artisan schedule:list
```

> Kemungkinan **"cronjob php" yang Anda lihat = baris `schedule:run` ini** (atau memang belum ada — kalau penyusutan tidak pernah terjadi tiap tanggal 1, berarti cron master ini belum dipasang).

---

## Ringkasan checklist produksi

**Untuk WA manual benar-benar terkirim:**
- [ ] Queue worker jalan (Bagian 1c/1d)
- [ ] Driver WA nyata dibuat + `WA_DRIVER` di `.env` (Bagian 2)
- [ ] `php artisan config:clear` setelah ubah `.env`/`config/wa.php`

**Untuk WA otomatis terjadwal (opsional):**
- [ ] Command `tagihan:kirim-terjadwal` (Bagian 3a)
- [ ] `Schedule::command(...)` di `routes/console.php` (Bagian 3b)
- [ ] Cron master `schedule:run` tiap menit (Bagian 4)
- [ ] Queue worker tetap harus jalan (job tetap di-queue)

**Untuk penyusutan bulanan yang sudah ada berfungsi:**
- [ ] Cukup cron master `schedule:run` (Bagian 4) — itu saja.

---

## Troubleshooting

| Gejala | Kemungkinan sebab | Solusi |
|---|---|---|
| Klik "Kirim WA", status tetap `antri` | Worker tidak jalan | Jalankan/cek queue worker (Bagian 1) |
| Status `terkirim` tapi WA tak sampai | Driver masih `log` | Set `WA_DRIVER` ke driver nyata (Bagian 2) |
| Driver nyata sudah diset, status `gagal` | Token salah / nomor format salah / saldo gateway habis | Cek kolom `response` di `notifikasi_tagihans`; pastikan nomor `62xxx` |
| Penyusutan tgl 1 tak jalan | Cron master `schedule:run` belum dipasang | Pasang crontab (Bagian 4) |
| Jadwal terdaftar tapi tetap diam | Worker untuk job ter-queue mati / waktu server beda zona | Cek `APP_TIMEZONE`, `php artisan schedule:list`, worker |
| Job berubah tapi tetap pakai kode lama | Worker belum restart | `php artisan queue:restart` |
