# Deployment SI Sekolah ke VPS

Panduan lengkap deploy aplikasi SI Sekolah ke VPS production. Cocok untuk Tencent Cloud, Alibaba Cloud, AWS Lightsail, DigitalOcean, dst.

**Target audience**: sysadmin / developer yang akan setup VPS pertama kali, atau yang mau update deployment existing.

---

## 1. Prasyarat

### Spesifikasi VPS Minimum

| Item | Minimum | Recommended |
|---|---|---|
| RAM | 2 GB | 4 GB |
| CPU | 1 core | 2 core |
| Storage | 20 GB SSD | 40 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| Bandwidth | 1 TB/bulan | unlimited |

### Software Stack

| Software | Versi | Fungsi |
|---|---|---|
| PHP | **8.5.5** | Runtime aplikasi |
| MySQL | 8.0+ | Database |
| Nginx | 1.18+ | Web server / reverse proxy |
| Composer | 2.x | PHP package manager |
| Node.js | 20+ | Asset build |
| Git | 2.x | Code deployment |
| Supervisor | (optional) | Queue worker manager |

### Yang Perlu Disiapkan Sebelum Mulai

- Akses SSH ke VPS (root atau user dengan sudo)
- IP publik VPS (mis. `43.133.156.101`)
- Domain (optional, recommended untuk HTTPS) — mis. `sekolah.example.com`
- Repository git aplikasi sudah di GitHub/GitLab
- Backup database existing (kalau ini bukan fresh install)

---

## 2. Initial Server Setup

### 2.1 Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### 2.2 Install PHP 8.5 + Extensions

Ubuntu 24.04 belum punya PHP 8.5 default. Tambahkan PPA Ondrej:

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

Install PHP 8.5 + extensions yang dibutuhkan Laravel:

```bash
sudo apt install -y \
  php8.5 \
  php8.5-fpm \
  php8.5-cli \
  php8.5-mysql \
  php8.5-mbstring \
  php8.5-xml \
  php8.5-curl \
  php8.5-zip \
  php8.5-bcmath \
  php8.5-gd \
  php8.5-intl \
  php8.5-redis \
  unzip
```

Verify:

```bash
php -v
# Output: PHP 8.5.5 ...
```

### 2.3 Install Composer

```bash
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

### 2.4 Install Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v
```

### 2.5 Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
sudo mysql_secure_installation
```

Pilih `Y` untuk semua konfirmasi keamanan, set root password yang kuat.

### 2.6 Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

Verify dengan akses `http://VPS_IP` di browser — harus muncul "Welcome to nginx".

### 2.7 Install Git

```bash
sudo apt install -y git
```

---

## 3. Setup Database

### 3.1 Buat Database & User

```bash
sudo mysql -u root -p
```

Di MySQL prompt:

```sql
CREATE DATABASE si_sekolah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'si_sekolah_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_DI_SINI';
GRANT ALL PRIVILEGES ON si_sekolah.* TO 'si_sekolah_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Penting**: ganti `PASSWORD_KUAT_DI_SINI` dengan password random minimal 16 karakter. Catat di password manager.

### 3.2 Verify Akses

```bash
mysql -u si_sekolah_user -p si_sekolah -e "SELECT 1;"
```

Kalau output `1`, berarti credentials benar.

---

## 4. Deploy Code

### 4.1 Clone Repository

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/USERNAME/si-sekolah.git
sudo chown -R $USER:$USER si-sekolah
cd si-sekolah
```

Ganti `USERNAME/si-sekolah` dengan path repository kamu. Kalau repository private, setup SSH key dulu atau pakai HTTPS dengan token.

### 4.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

`--no-dev` skip package development (Pest, Pint, dll) — kecilin size production.

### 4.3 Setup `.env`

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Edit field-field penting:

```env
APP_NAME="SI Sekolah"
APP_ENV=production
APP_KEY=base64:XXXX  # auto-generated, jangan ubah
APP_DEBUG=false       # WAJIB false di production
APP_URL=http://43.133.156.101:8081  # atau https://domain.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=si_sekolah
DB_USERNAME=si_sekolah_user
DB_PASSWORD=PASSWORD_KUAT_DI_SINI  # sama dengan langkah 3.1

CACHE_STORE=database     # atau redis kalau install redis
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Filesystem
FILESYSTEM_DISK=local

# Timezone
APP_TIMEZONE=Asia/Jakarta
```

Save & exit (`Ctrl+O`, `Enter`, `Ctrl+X`).

### 4.4 Run Migration & Seed Awal

**WARNING**: Kalau ini fresh install, jalankan semua. Kalau update existing, **HANYA `migrate` dan `RoleSeeder`** seperti dijelaskan di bagian "Updating Existing Deployment" di bawah.

Untuk **fresh install** baru:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Untuk **update existing dengan data production**:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
```

`--force` wajib di production karena artisan biasanya tanya konfirmasi yang ga jalan di non-interactive shell.

### 4.5 Set Permission

```bash
sudo chown -R www-data:www-data /var/www/si-sekolah
sudo chmod -R 775 /var/www/si-sekolah/storage
sudo chmod -R 775 /var/www/si-sekolah/bootstrap/cache
```

`www-data` itu user default Nginx/PHP-FPM di Ubuntu. Pakai user yang tepat sesuai distro kamu.

### 4.6 Buat Super Admin Pertama

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@sekolahmu.sch.id',
    'password' => bcrypt('password-sangat-kuat-disini'),
]);
$user->assignRole('super_admin');
exit
```

Catat email + password untuk login pertama kali.

---

## 5. Setup Nginx (Listen Port 8081)

### 5.1 Buat Nginx Server Block

```bash
sudo nano /etc/nginx/sites-available/si-sekolah
```

Isi dengan config berikut. Sesuaikan `server_name` dan `root` dengan VPS-mu:

```nginx
server {
    listen 8081;
    listen [::]:8081;
    server_name 43.133.156.101;

    root /var/www/si-sekolah/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 20M;

    # Logging
    access_log /var/log/nginx/si-sekolah-access.log;
    error_log  /var/log/nginx/si-sekolah-error.log;

    # Laravel default rewrite
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60s;
    }

    # Block access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static assets caching (image, css, js, fonts)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2?|ttf|svg)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, no-transform";
    }

    # Disable favicon log spam
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
}
```

### 5.2 Aktifkan Site

```bash
sudo ln -s /etc/nginx/sites-available/si-sekolah /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` test konfigurasi syntax. Kalau ada error, perbaiki sebelum reload.

### 5.3 Buka Firewall (UFW)

```bash
sudo ufw allow 22/tcp     # SSH (jangan sampai ke-lock!)
sudo ufw allow 80/tcp     # HTTP (kalau pakai default port nanti)
sudo ufw allow 443/tcp    # HTTPS (kalau pakai SSL nanti)
sudo ufw allow 8081/tcp   # SI Sekolah port
sudo ufw enable
sudo ufw status
```

### 5.4 Buka Port di Cloud Provider Security Group

**Penting**: firewall OS saja tidak cukup. Provider VPS punya firewall layer lain.

**Tencent Cloud:**
1. Login console → CVM → pilih instance
2. Security Groups → Edit
3. Inbound Rule → Add Rule:
   - Type: Custom TCP
   - Port Range: `8081`
   - Source: `0.0.0.0/0` (semua IP) atau IP WiFi sekolah saja
   - Action: Accept

**AWS Lightsail / EC2:**
1. Networking tab → Add rule
2. Application: Custom
3. Protocol: TCP, Port: 8081
4. Save

**DigitalOcean:**
1. Networking → Firewalls
2. Inbound Rule → TCP port 8081

### 5.5 Test dari Luar VPS

Dari laptop yang **TIDAK** di VPS:

```bash
curl -v http://43.133.156.101:8081
```

Output yang benar:
- HTTP 200 atau 302 (redirect ke /auth/login) → **OK**
- `Connection refused` → Nginx tidak listen di port itu (cek `sudo ss -tlnp | grep 8081`)
- `Connection timeout` → firewall block (cek UFW + cloud security group)

---

## 6. Optimize untuk Production

### 6.1 Cache Configuration

```bash
cd /var/www/si-sekolah
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components
```

Ini bikin Laravel boot 2-3x lebih cepat. Setiap kali update `.env` atau routing, jangan lupa rerun cache atau clear dulu:

```bash
php artisan optimize:clear
```

### 6.2 Setup Queue Worker (Supervisor)

Aplikasi pakai job (mis. notif WhatsApp ke wali). Wajib ada queue worker yang running terus.

Install Supervisor:

```bash
sudo apt install -y supervisor
```

Buat config:

```bash
sudo nano /etc/supervisor/conf.d/si-sekolah-worker.conf
```

```ini
[program:si-sekolah-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/si-sekolah/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/si-sekolah-worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start si-sekolah-worker:*
sudo supervisorctl status
```

Output harus `RUNNING`. Kalau gagal, cek log: `sudo tail -f /var/log/supervisor/si-sekolah-worker.log`.

### 6.3 Setup Cron untuk Schedule

```bash
sudo crontab -e -u www-data
```

Tambahkan baris:

```cron
* * * * * cd /var/www/si-sekolah && php artisan schedule:run >> /dev/null 2>&1
```

Ini memastikan task scheduler Laravel jalan tiap menit.

### 6.4 PHP-FPM Tuning (Optional, untuk traffic tinggi)

```bash
sudo nano /etc/php/8.5/fpm/pool.d/www.conf
```

Sesuaikan untuk RAM 4 GB:

```ini
pm = dynamic
pm.max_children = 30
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500
```

Restart:

```bash
sudo systemctl restart php8.5-fpm
```


---

## 7. HTTPS Setup (Optional, Recommended)

Kalau punya domain, setup HTTPS gratis dengan Let's Encrypt + Certbot.

### 7.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 7.2 Generate Certificate

Pastikan domain sudah point ke IP VPS via DNS A record dulu.

```bash
sudo certbot --nginx -d sekolah.example.com
```

Ikuti prompt:
- Email untuk notifikasi expire
- Agree TOS: `Y`
- Share email: `N` (optional)
- Redirect HTTP → HTTPS: `2` (recommended)

Certbot otomatis edit Nginx config dan reload. Test dari browser: `https://sekolah.example.com` harus muncul gembok hijau.

### 7.3 Auto-Renew

Certbot install systemd timer otomatis. Verify:

```bash
sudo systemctl status certbot.timer
sudo certbot renew --dry-run
```

Cert auto-renew tiap 90 hari.

### 7.4 Update `.env` & Firmware ESP32

Setelah HTTPS aktif, update `.env`:

```env
APP_URL=https://sekolah.example.com
```

Lalu clear cache:

```bash
php artisan optimize:clear
php artisan config:cache
```

Update firmware ESP32 ganti API_URL ke `https://sekolah.example.com/api/rfid/scan`, upload ulang ke setiap reader.

---

## 8. Update Existing Deployment (Untuk Update Code)

Setelah deploy pertama, update versi baru pakai langkah ini. **JANGAN run `migrate:fresh` di production — DATA HILANG SEMUA!**

### 8.1 Buat Script `deploy.sh`

```bash
nano /var/www/si-sekolah/deploy.sh
```

Isi:

```bash
#!/usr/bin/env bash
set -e

cd /var/www/si-sekolah

echo "==> Backup database..."
mkdir -p storage/backups
mysqldump -u si_sekolah_user -p"$DB_PASSWORD" si_sekolah | gzip > "storage/backups/db_$(date +%Y%m%d_%H%M%S).sql.gz"

echo "==> Maintenance mode ON..."
php artisan down --refresh=15 || true

echo "==> Pull latest code..."
git pull origin main

echo "==> Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> NPM build..."
npm ci
npm run build

echo "==> Database migrate..."
php artisan migrate --force

echo "==> Seed roles (idempotent, safe)..."
php artisan db:seed --class=RoleSeeder --force

echo "==> Clear cache..."
php artisan optimize:clear

echo "==> Rebuild cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components

echo "==> Generate Swagger docs..."
php artisan l5-swagger:generate

echo "==> Restart queue workers..."
sudo supervisorctl restart si-sekolah-worker:*

echo "==> Maintenance mode OFF..."
php artisan up

echo "==> Done!"
```

Buat executable:

```bash
chmod +x /var/www/si-sekolah/deploy.sh
```

### 8.2 Cara Update

```bash
cd /var/www/si-sekolah
export DB_PASSWORD='password-di-sini'
./deploy.sh
```

Atau lebih bersih, setup environment variable di `~/.bashrc`:

```bash
echo 'export DB_PASSWORD="password-di-sini"' >> ~/.bashrc
source ~/.bashrc
```

### 8.3 Rollback Kalau Gagal

```bash
cd /var/www/si-sekolah

# Rollback code ke commit sebelum
git log --oneline -5
git reset --hard COMMIT_HASH_SEBELUM

# Restore database (kalau ada migration baru)
gunzip < storage/backups/db_YYYYMMDD_HHMMSS.sql.gz | mysql -u si_sekolah_user -p si_sekolah

# Re-cache
php artisan optimize:clear
php artisan config:cache

php artisan up
```

---

## 9. Monitoring & Backup

### 9.1 Log Files

| Log | Lokasi |
|---|---|
| Application | `/var/www/si-sekolah/storage/logs/laravel-*.log` |
| Nginx access | `/var/log/nginx/si-sekolah-access.log` |
| Nginx error | `/var/log/nginx/si-sekolah-error.log` |
| Queue worker | `/var/log/supervisor/si-sekolah-worker.log` |
| PHP-FPM | `/var/log/php8.5-fpm.log` |

Tail real-time:

```bash
tail -f /var/www/si-sekolah/storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 9.2 Auto Backup Database (Cron)

```bash
sudo crontab -e -u www-data
```

Tambahkan:

```cron
0 2 * * * cd /var/www/si-sekolah && mysqldump -u si_sekolah_user -p"PASSWORD" si_sekolah | gzip > storage/backups/db_$(date +\%Y\%m\%d).sql.gz
0 3 * * 0 find /var/www/si-sekolah/storage/backups -name "*.sql.gz" -mtime +30 -delete
```

Backup harian jam 02:00, hapus backup >30 hari setiap Minggu jam 03:00.

### 9.3 Disk Usage Monitoring

```bash
df -h         # disk usage
du -sh /var/www/si-sekolah/storage/*  # storage breakdown
```

Kalau storage menipis, hapus log lama:

```bash
find /var/www/si-sekolah/storage/logs -name "*.log" -mtime +30 -delete
```

---

## 10. Troubleshooting

### 10.1 "500 Internal Server Error" di Browser

```bash
tail -50 /var/www/si-sekolah/storage/logs/laravel-$(date +%Y-%m-%d).log
```

Cek error message detail. Common issues:
- **Permission**: `sudo chown -R www-data:www-data /var/www/si-sekolah/storage`
- **APP_KEY missing**: `php artisan key:generate`
- **DB connection**: cek `.env` DB credentials

### 10.2 "502 Bad Gateway"

PHP-FPM tidak running atau socket salah:

```bash
sudo systemctl status php8.5-fpm
sudo systemctl restart php8.5-fpm
```

Cek socket path di Nginx config sama dengan output:

```bash
sudo grep -r "listen" /etc/php/8.5/fpm/pool.d/
```

### 10.3 Queue Job Tidak Diproses

```bash
sudo supervisorctl status
sudo tail -f /var/log/supervisor/si-sekolah-worker.log
```

Restart kalau stuck:

```bash
sudo supervisorctl restart si-sekolah-worker:*
```

### 10.4 RFID API Reject Semua Request

- Cek device `is_active=true` di Filament
- Token di firmware ESP32 sama dengan yang di-generate di Filament
- Cek log: `tail -f storage/logs/laravel-*.log`
- Cek `Pengaturan → Log Scan RFID` di Filament untuk lihat semua tap (termasuk yang gagal)

### 10.5 Nginx Tidak Listen di Port 8081

```bash
sudo nginx -t                          # cek syntax
sudo systemctl status nginx            # cek status
sudo ss -tlnp | grep nginx             # cek port listening
```

### 10.6 SSL Certificate Expired

```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## 11. Post-Deploy Checklist

Sebelum kasih ke user, validate semua ini:

- [ ] Akses web `http://VPS_IP:8081` muncul login Filament
- [ ] Login dengan super admin → dashboard tampil
- [ ] Akses Swagger `/api/documentation` → endpoint terlihat
- [ ] Test API via Swagger atau curl → 401 (token salah) / 200 (token benar)
- [ ] Tap kartu di ESP32 → response masuk ke `Log Scan RFID`
- [ ] Queue worker running (`supervisorctl status`)
- [ ] Cron schedule running (cek log)
- [ ] Backup database otomatis aktif (`crontab -l -u www-data`)
- [ ] Firewall hanya buka port yang dibutuhkan (`sudo ufw status`)
- [ ] APP_DEBUG=false di `.env`
- [ ] APP_ENV=production di `.env`
- [ ] PHP error display OFF (default kalau APP_DEBUG=false)
- [ ] HTTPS aktif (kalau sudah punya domain)

---

## 12. Security Hardening

### 12.1 Disable SSH Password (Pakai Key Only)

```bash
sudo nano /etc/ssh/sshd_config
```

```
PasswordAuthentication no
PubkeyAuthentication yes
PermitRootLogin no
```

Restart SSH:

```bash
sudo systemctl restart ssh
```

**WARNING**: pastikan SSH key kamu sudah works sebelum ini, atau kamu lock-out dari VPS.

### 12.2 Fail2ban (Block IP Brute Force)

```bash
sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
```

Default config sudah cukup proteksi SSH. Untuk Nginx, edit:

```bash
sudo nano /etc/fail2ban/jail.local
```

```ini
[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
```

Restart:

```bash
sudo systemctl restart fail2ban
```

### 12.3 File Permission Lock-down

```bash
cd /var/www/si-sekolah
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 600 .env
```

`.env` mode 600 supaya hanya owner bisa baca (bukan world-readable).

### 12.4 Rate Limit API

Edit `routes/api.php`:

```php
Route::middleware(['rfid.device', 'throttle:60,1'])->group(function () {
    Route::post('/rfid/scan', [RfidScanController::class, 'store']);
});
```

`60,1` = 60 request per menit per device. Sesuaikan kalau perlu.

---

## 13. FAQ Deployment

**Q: Apakah PHP 8.5 stabil untuk production?**
A: PHP 8.5 sudah stable rilis akhir 2025. Aplikasi ini tested di PHP 8.5.5. Kalau VPS-mu cuma support PHP 8.3/8.4, project ini tetap jalan (Laravel 12 require PHP 8.2+).

**Q: Bisa pakai Apache instead of Nginx?**
A: Bisa, tapi config beda. Nginx lebih cepat dan ringan untuk Laravel. Recommend Nginx.

**Q: Bisa pakai SQLite/PostgreSQL?**
A: Bisa, tinggal ubah `DB_CONNECTION` di `.env`. Tapi seeder dan beberapa query SQL spesifik MySQL mungkin perlu adjustment.

**Q: Bagaimana scale kalau user banyak?**
A: Step pertama: upgrade RAM/CPU VPS. Step kedua: pisah database server. Step ketiga: load balancer + multi-server. Untuk sekolah single, 1 VPS 4GB RAM cukup untuk ribuan user.

**Q: Berapa cost VPS bulanan?**
A: Rp 100rb-300rb tergantung provider dan spec. Tencent Cloud ID ada paket Rp 90rb/bulan untuk 2 GB RAM.

**Q: Bisa deploy via Docker?**
A: Bisa, pakai Laravel Sail atau buat Dockerfile sendiri. Ada di project (`composer.json` ada `laravel/sail`). Tapi panduan ini untuk bare metal VPS yang lebih umum.

