# Implementasi RFID Reader dengan ESP32 + RC522

Dokumen ini panduan lengkap untuk membuat RFID reader berbasis **ESP32 + RC522** yang terintegrasi dengan endpoint API SI Sekolah (`POST /api/rfid/scan`).

**Target audience**: tim IT sekolah / teknisi yang akan merakit dan deploy RFID reader di gerbang sekolah.

---

## 1. Daftar Komponen Hardware

### Wajib

| Komponen | Spesifikasi | Estimasi Harga |
|---|---|---|
| **ESP32 DevKit V1** | 30-pin, WiFi + Bluetooth | Rp 50-80rb |
| **RC522 RFID Reader** | Mifare 13.56MHz | Rp 15-25rb |
| **Kabel jumper female-female** | minimal 8 buah | Rp 10rb |
| **Kabel USB Micro-B** | data + charging | Rp 10rb |
| **Power adapter 5V 1A** | dengan port USB | Rp 25rb |
| **Kartu RFID Mifare 1K** | 13.56MHz, jumlah sesuai siswa+pegawai | Rp 3-5rb/kartu |

### Opsional (Recommended untuk UX)

| Komponen | Fungsi | Estimasi Harga |
|---|---|---|
| **OLED Display 0.96" I2C** | tampilkan nama siswa + status | Rp 30-40rb |
| **Buzzer Aktif 5V** | beep saat tap berhasil/gagal | Rp 5rb |
| **LED 5mm hijau + merah** | indikator visual | Rp 2rb |
| **Resistor 220 ohm** (2 buah) | untuk LED | Rp 1rb |
| **Project box / casing** | lindungi rangkaian | Rp 30-50rb |

**Total estimasi**: Rp 200-300rb per device (belum termasuk kartu).

---

## 2. Wiring / Pinout Diagram

### RC522 → ESP32

| RC522 Pin | ESP32 Pin | Keterangan |
|---|---|---|
| `SDA` (SS) | GPIO 5 | Slave Select |
| `SCK` | GPIO 18 | SPI Clock |
| `MOSI` | GPIO 23 | SPI Master Out |
| `MISO` | GPIO 19 | SPI Master In |
| `IRQ` | (kosongkan) | tidak dipakai |
| `GND` | GND | Ground |
| `RST` | GPIO 22 | Reset |
| `3.3V` | 3.3V | **JANGAN ke 5V — RC522 hanya tahan 3.3V** |

### Komponen Opsional

**OLED 0.96" I2C** → ESP32:
- `VCC` → 3.3V
- `GND` → GND
- `SCL` → GPIO 22 (**konflik dengan RC522 RST!** — pindahkan RST RC522 ke GPIO 4)
- `SDA` → GPIO 21

> **Catatan pinout**: kalau pakai OLED, ubah RC522 `RST` ke **GPIO 4** untuk menghindari konflik I2C SCL.

**Buzzer** → ESP32:
- `+` → GPIO 25
- `-` → GND

**LED** → ESP32 (via resistor 220 ohm):
- LED hijau (sukses) → GPIO 26
- LED merah (gagal) → GPIO 27

### Diagram Sederhana

```
        ESP32                          RC522
       ┌─────┐                       ┌─────┐
       │ 3V3 │───────────────────────│ 3.3V│
       │ GND │───────────────────────│ GND │
       │ G5  │───────────────────────│ SDA │
       │ G18 │───────────────────────│ SCK │
       │ G23 │───────────────────────│ MOSI│
       │ G19 │───────────────────────│ MISO│
       │ G4  │───────────────────────│ RST │  (kalau pakai OLED)
       │ G22 │── (atau pindah sini kalau tanpa OLED) ──┘
       │ G21 │── SDA ─── OLED        │
       │ G22 │── SCL ─── OLED        │
       │ G25 │── + ───── Buzzer      │
       │ G26 │── + ───── LED Hijau   │
       │ G27 │── + ───── LED Merah   │
       └─────┘                       └─────┘
```

---

## 3. Setup Arduino IDE

### 3.1 Install Arduino IDE

Download dari [arduino.cc/en/software](https://www.arduino.cc/en/software). Install versi 2.x (terbaru).

### 3.2 Tambah ESP32 Board Manager

1. Buka **File → Preferences**
2. Di **Additional Boards Manager URLs**, tambahkan:
   ```
   https://espressif.github.io/arduino-esp32/package_esp32_index.json
   ```
3. **Tools → Board → Boards Manager** → search "esp32" → Install **esp32 by Espressif Systems**
4. **Tools → Board → ESP32 Arduino → ESP32 Dev Module**

### 3.3 Install Library

**Tools → Manage Libraries**, search dan install:

| Library | Author | Versi |
|---|---|---|
| **MFRC522** | GithubCommunity | terbaru |
| **ArduinoJson** | Benoit Blanchon | 6.x atau 7.x |
| **WiFi** | (built-in ESP32) | — |
| **HTTPClient** | (built-in ESP32) | — |
| **Adafruit GFX Library** | Adafruit | terbaru (kalau pakai OLED) |
| **Adafruit SSD1306** | Adafruit | terbaru (kalau pakai OLED) |

### 3.4 Konfigurasi Port

1. Hubungkan ESP32 ke laptop via USB
2. **Tools → Port** → pilih port yang muncul (mis. `COM3` di Windows, `/dev/cu.usbserial-XXXX` di macOS)
3. Kalau port tidak muncul: install driver CP210x atau CH340 sesuai chip USB-to-Serial di board ESP32 kamu

---

## 4. Persiapan di Sisi Server (Laravel)

Sebelum upload firmware, dapatkan dulu **API token** dari Filament:

1. Login ke `/auth/login` sebagai super_admin
2. **Pengaturan → RFID Device → New RFID Device**
3. Isi:
   - **Nama**: `Reader Gerbang Utama (Masuk)`
   - **Kode**: `GERBANG-IN-01`
   - **Jenis**: `Gerbang Masuk`
   - **Lokasi**: `Gerbang Utama Depan`
   - **Aktif**: ON
4. Klik **Create**
5. **PENTING**: Notification akan muncul dengan **plain token** (60 karakter random). Copy & simpan **sekarang juga** karena ini hanya tampil sekali. Contoh:
   ```
   API Token: aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX4yZ5aB6cD7eF8gH9iJ0kL1mN2oP3qR
   ```

Catat juga URL server VPS-mu, contoh:
```
https://sekolah.example.com/api/rfid/scan
```

---

(continued in next chunk...)

## 5. Firmware ESP32 — Versi Basic (tanpa OLED/buzzer)

Buat file baru di Arduino IDE: **File → New Sketch**, copy kode di bawah, save dengan nama `rfid_reader_basic.ino`.

```cpp
#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <time.h>

// ===== KONFIGURASI WAJIB DIUBAH =====
const char* WIFI_SSID = "NamaWiFiSekolah";
const char* WIFI_PASSWORD = "passwordWiFi";

// API_URL bisa pakai HTTPS domain ATAU HTTP IP+port
// Contoh HTTPS (recommended untuk production):
//   const char* API_URL = "https://sekolah.example.com/api/rfid/scan";
// Contoh HTTP IP+port (dev/staging, atau kalau belum ada domain):
//   const char* API_URL = "http://203.0.113.45:8000/api/rfid/scan";
const char* API_URL = "https://sekolah.example.com/api/rfid/scan";
const char* API_TOKEN = "aB3cD4eF5gH6iJ7kL8mN9oP0qR1sT2uV3wX4yZ5aB6cD7eF8gH9iJ0kL1mN2oP3qR";
const char* DEVICE_KODE = "GERBANG-IN-01";

// Timezone Asia/Jakarta (WIB = UTC+7)
const long  GMT_OFFSET_SEC = 7 * 3600;
const int   DAYLIGHT_OFFSET_SEC = 0;
const char* NTP_SERVER = "pool.ntp.org";

// ===== PIN CONFIGURATION =====
#define RST_PIN 22  // RC522 RST (pakai 4 kalau ada OLED)
#define SS_PIN  5   // RC522 SDA/SS

MFRC522 mfrc522(SS_PIN, RST_PIN);

// ===== SETUP =====
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n[SI Sekolah RFID Reader] Booting...");

  // Init SPI + RC522
  SPI.begin();
  mfrc522.PCD_Init();
  delay(50);
  Serial.println("RC522 ready");

  // Connect WiFi
  connectWiFi();

  // Sync waktu via NTP
  configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, NTP_SERVER);
  Serial.print("Sync NTP");
  while (time(nullptr) < 100000) {
    Serial.print(".");
    delay(500);
  }
  Serial.println(" OK");

  Serial.println("Ready. Tap your card...");
}

// ===== MAIN LOOP =====
void loop() {
  // Cek WiFi, reconnect kalau putus
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected, reconnecting...");
    connectWiFi();
    return;
  }

  // Cari kartu
  if (!mfrc522.PICC_IsNewCardPresent()) {
    return;
  }
  if (!mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  // Baca UID kartu, format hex uppercase tanpa separator
  String uid = readUid();
  Serial.print("Card detected: ");
  Serial.println(uid);

  // Kirim ke server
  sendScan(uid);

  // Halt kartu, jangan baca terus-menerus
  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();

  // Anti tap-bouncing di sisi device (1 detik)
  delay(1000);
}

// ===== HELPER: WiFi =====
void connectWiFi() {
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Connecting WiFi");
  unsigned long start = millis();

  while (WiFi.status() != WL_CONNECTED) {
    if (millis() - start > 30000) {
      Serial.println("\nWiFi timeout, restarting ESP32...");
      ESP.restart();
    }
    Serial.print(".");
    delay(500);
  }
  Serial.print("\nIP: ");
  Serial.println(WiFi.localIP());
}

// ===== HELPER: Read UID =====
String readUid() {
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

// ===== HELPER: Send to API =====
void sendScan(String uid) {
  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.setTimeout(10000);

  // Build JSON payload
  StaticJsonDocument<256> doc;
  doc["uid"] = uid;
  doc["device_kode"] = DEVICE_KODE;
  doc["scanned_at"] = isoTimestamp();

  String payload;
  serializeJson(doc, payload);

  Serial.print("POST: ");
  Serial.println(payload);

  int httpCode = http.POST(payload);

  if (httpCode > 0) {
    String response = http.getString();
    Serial.print("Response (");
    Serial.print(httpCode);
    Serial.print("): ");
    Serial.println(response);

    handleResponse(httpCode, response);
  } else {
    Serial.print("HTTP error: ");
    Serial.println(http.errorToString(httpCode));
  }

  http.end();
}

// ===== HELPER: Handle Response =====
void handleResponse(int httpCode, String response) {
  if (httpCode == 401) {
    Serial.println("AUTH GAGAL — cek token!");
    return;
  }

  StaticJsonDocument<512> doc;
  DeserializationError err = deserializeJson(doc, response);
  if (err) {
    Serial.println("JSON parse error");
    return;
  }

  bool success = doc["success"] | false;
  const char* jenis = doc["jenis"] | "unknown";
  const char* pesan = doc["pesan"] | "";

  Serial.print("Result: ");
  Serial.print(jenis);
  Serial.print(" | ");
  Serial.println(pesan);

  if (success) {
    const char* nama = doc["pemilik"]["nama"] | "-";
    const char* tipe = doc["pemilik"]["tipe"] | "-";
    Serial.print("Pemilik: ");
    Serial.print(nama);
    Serial.print(" (");
    Serial.print(tipe);
    Serial.println(")");
  }
}

// ===== HELPER: ISO Timestamp =====
String isoTimestamp() {
  time_t now = time(nullptr);
  struct tm timeinfo;
  localtime_r(&now, &timeinfo);

  char buf[32];
  // Format: 2026-05-24T07:05:32+07:00
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%S+07:00", &timeinfo);
  return String(buf);
}
```

### 5.1 Upload ke ESP32

1. **Tools → Board** → ESP32 Dev Module
2. **Tools → Port** → pilih port ESP32
3. **Tools → Upload Speed** → 921600
4. Klik **Upload** (icon panah → di toolbar)
5. Tunggu compile (~30-60 detik)
6. Saat muncul "Connecting......" tekan tombol **BOOT** di ESP32 (kadang perlu)
7. Setelah selesai, buka **Tools → Serial Monitor**, set baud rate **115200**
8. Tap kartu RFID — output di serial monitor:
   ```
   Card detected: 04A1B2C3
   POST: {"uid":"04A1B2C3","device_kode":"GERBANG-IN-01","scanned_at":"2026-05-24T07:05:32+07:00"}
   Response (200): {"jenis":"masuk","pesan":"Selamat datang Ahmad",...}
   Result: masuk | Selamat datang Ahmad
   Pemilik: Ahmad Setiawan (siswa)
   ```


---

## 6. Firmware ESP32 — Versi Lengkap (dengan OLED + Buzzer + LED)

Versi ini memberikan feedback visual (nama siswa di OLED) dan auditori (buzzer beep). Wajib pakai pinout yang sudah dimodifikasi (`RST_PIN = 4`).

```cpp
#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <time.h>

// ===== KONFIGURASI =====
const char* WIFI_SSID = "NamaWiFiSekolah";
const char* WIFI_PASSWORD = "passwordWiFi";
const char* API_URL = "https://sekolah.example.com/api/rfid/scan";
const char* API_TOKEN = "YOUR-API-TOKEN-HERE";
const char* DEVICE_KODE = "GERBANG-IN-01";

// ===== PIN =====
#define RST_PIN 4
#define SS_PIN  5
#define BUZZER_PIN 25
#define LED_GREEN_PIN 26
#define LED_RED_PIN 27
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
#define SCREEN_ADDRESS 0x3C

MFRC522 mfrc522(SS_PIN, RST_PIN);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// ===== SETUP =====
void setup() {
  Serial.begin(115200);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(LED_GREEN_PIN, OUTPUT);
  pinMode(LED_RED_PIN, OUTPUT);

  // OLED init
  Wire.begin(21, 22);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println("OLED init failed");
  }
  showMessage("SI Sekolah", "Booting...", 0);

  SPI.begin();
  mfrc522.PCD_Init();

  connectWiFi();

  configTime(7 * 3600, 0, "pool.ntp.org");
  while (time(nullptr) < 100000) {
    delay(500);
  }

  showMessage("Ready", "Tempelkan kartu", 0);
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    showMessage("WiFi", "Disconnect", 2);
    connectWiFi();
    return;
  }

  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  String uid = readUid();
  showMessage("Memproses...", uid, 0);
  beep(50);

  sendScan(uid);

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
  delay(2000);

  showMessage("Ready", "Tempelkan kartu", 0);
}

void connectWiFi() {
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED) {
    if (millis() - start > 30000) ESP.restart();
    delay(500);
  }
}

String readUid() {
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

void sendScan(String uid) {
  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
  http.setTimeout(10000);

  StaticJsonDocument<256> doc;
  doc["uid"] = uid;
  doc["device_kode"] = DEVICE_KODE;
  doc["scanned_at"] = isoTimestamp();

  String payload;
  serializeJson(doc, payload);

  int code = http.POST(payload);
  if (code > 0) {
    handleResponse(code, http.getString());
  } else {
    showMessage("Network Error", http.errorToString(code), 2);
    beepError();
  }
  http.end();
}

void handleResponse(int code, String response) {
  if (code == 401) {
    showMessage("AUTH GAGAL", "Cek token", 2);
    beepError();
    return;
  }

  StaticJsonDocument<512> doc;
  if (deserializeJson(doc, response)) {
    showMessage("Server Error", "Invalid JSON", 2);
    beepError();
    return;
  }

  bool success = doc["success"] | false;
  const char* jenis = doc["jenis"] | "unknown";
  const char* pesan = doc["pesan"] | "";

  if (success) {
    const char* nama = doc["pemilik"]["nama"] | "-";
    String header = String(jenis);
    header.toUpperCase();
    showMessage(header, nama, 1);
    beepSuccess();
  } else {
    showMessage("DITOLAK", pesan, 2);
    beepError();
  }
}

String isoTimestamp() {
  time_t now = time(nullptr);
  struct tm t;
  localtime_r(&now, &t);
  char buf[32];
  strftime(buf, sizeof(buf), "%Y-%m-%dT%H:%M:%S+07:00", &t);
  return String(buf);
}

// ===== FEEDBACK =====
// state: 0=idle, 1=success, 2=error
void showMessage(String line1, String line2, int state) {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  display.setTextSize(2);
  display.setCursor(0, 0);
  display.println(line1);

  display.setTextSize(1);
  display.setCursor(0, 30);
  display.println(line2);
  display.display();

  digitalWrite(LED_GREEN_PIN, state == 1 ? HIGH : LOW);
  digitalWrite(LED_RED_PIN, state == 2 ? HIGH : LOW);
}

void beep(int duration) {
  digitalWrite(BUZZER_PIN, HIGH);
  delay(duration);
  digitalWrite(BUZZER_PIN, LOW);
}

void beepSuccess() {
  beep(100); delay(50); beep(100);
}

void beepError() {
  beep(500);
}
```


---

## 7. Workflow Registrasi Kartu Baru

Sebelum kartu bisa dipakai untuk presensi, harus didaftarkan di sistem dulu. Ada 2 cara:

### 7.1 Cara Manual via Filament Panel

1. Tap kartu di reader yang sudah running, lihat **Serial Monitor** untuk dapat UID (mis. `04A1B2C3`)
2. Login Filament → **Kesiswaan → Kartu RFID → New Kartu RFID**
3. Pilih **Tipe Pemilik**: Siswa atau Pegawai
4. Pilih **Pemilik**: cari nama
5. Paste **UID** dari Serial Monitor
6. **Status**: Aktif
7. Save

### 7.2 Cara Mass Registration (untuk awal deployment)

Untuk daftarkan ratusan siswa sekaligus, buat **mode registrasi** di firmware. Ganti sementara `loop()` di firmware untuk hanya print UID:

```cpp
void loop() {
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    return;
  }
  String uid = readUid();
  Serial.print("UID: ");
  Serial.println(uid);
  delay(1500);
  mfrc522.PICC_HaltA();
}
```

Kemudian:
1. Siapkan spreadsheet dengan kolom: `nis_atau_nip`, `nama`, `uid`
2. Petugas tap satu per satu kartu, isi UID di spreadsheet
3. Setelah semua selesai, import via Filament (kalau resource sudah punya import action) atau via tinker:

```bash
php artisan tinker
```

```php
use App\Models\KartuRfid;
use App\Models\Siswa;

$data = [
    ['nis' => '12345', 'uid' => '04A1B2C3'],
    ['nis' => '12346', 'uid' => '04A1B2C4'],
    // ...
];

foreach ($data as $row) {
    $siswa = Siswa::where('nis', $row['nis'])->first();
    if (!$siswa) continue;

    KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => $row['uid'],
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);
}
```

Setelah selesai, kembalikan `loop()` firmware ke versi aslinya dan upload ulang.

---

## 8. Deployment di Lapangan

### 8.1 Casing & Pemasangan

- Masukkan ESP32 + RC522 ke project box dengan lubang untuk antena RC522 (jangan tutup logam — RFID tidak akan bekerja)
- OLED dipasang di permukaan luar box yang menghadap siswa
- Buzzer + LED juga di permukaan luar
- Power adapter 5V dipasang di colokan listrik dekat gerbang
- Casing harus tahan air kalau dipasang outdoor — pakai box IP65 atau pasang di atap teritisan

### 8.2 Power & Network

- **Power**: pakai adapter 5V 1A bermerek (jangan abal-abal — ESP32 sensitif terhadap voltage drop)
- **WiFi**: pastikan router sekolah cover area gerbang. Kalau lemah, pasang **WiFi extender** atau pakai access point khusus
- **Backup koneksi** (optional): pakai modem 4G + router WiFi kalau jaringan utama sering down

### 8.3 Multiple Devices

Setiap reader harus punya **token API berbeda** dan **kode device unik**. Contoh setup sekolah dengan 2 gerbang:

| Lokasi | Kode Device | Jenis | Catatan |
|---|---|---|---|
| Gerbang Utama Masuk | `GERBANG-IN-01` | gerbang_masuk | Reader tunggal di pos satpam |
| Gerbang Utama Pulang | `GERBANG-OUT-01` | gerbang_pulang | Sebelahnya, terpisah |

Kalau **1 reader saja** untuk masuk + pulang, set jenis `serbaguna`. Server otomatis tentukan masuk vs pulang berdasarkan apakah siswa sudah tap hari itu atau belum.

---

## 9. Troubleshooting

### 9.1 RC522 tidak terdeteksi (`Card detected` tidak muncul)

- Cek wiring 3.3V dan GND
- **JANGAN pakai 5V ke RC522** — bisa rusak permanen
- Coba ganti jumper kabel — kabel jelek sering jadi penyebab
- Coba kartu lain — kartu rusak jarang tapi bisa kejadian

### 9.2 WiFi connect lalu disconnect terus

- Pastikan WiFi 2.4 GHz (ESP32 tidak support 5 GHz)
- Cek password WiFi
- Sinyal lemah → pasang lebih dekat ke router atau extender

### 9.3 HTTP Error -1, -11, atau timeout

- URL salah ketik
- HTTPS certificate issue → pastikan VPS pakai SSL valid (Let's Encrypt). Kalau pakai self-signed cert, perlu `client.setInsecure()` (tidak direkomendasikan untuk production)
- Firewall VPS blokir traffic — buka port 443

### 9.4 Response 401 Unauthorized

- Token salah atau corrupted saat copy-paste — generate ulang via Filament
- Device di Filament status `is_active=false`
- Token sudah pernah di-regenerate — token lama otomatis tidak berlaku

### 9.5 Response sukses tapi presensi tidak tercatat

- Cek **Pengaturan → Log Scan RFID** di Filament — semua tap tercatat di sini termasuk yang `tidak_dikenal`/`ditolak`/`duplikat`
- Pastikan UID yang dibaca cocok dengan UID yang diregister (perhatikan format uppercase tanpa separator)

### 9.6 Tap pertama selalu duplikat

- Kemungkinan reader baca kartu 2x karena sentuhan terlalu lama
- Naikkan `delay(1000)` di firmware jadi `delay(2000)`
- Atau di server, naikkan `debounce_scan_detik` di Pengaturan → Sekolah

### 9.7 ESP32 reboot terus

- Power supply tidak cukup — ganti adapter 5V 2A
- Memory leak / variable too big — kurangi log Serial.println()

---

## 10. Production Checklist

Sebelum deploy ke gerbang sekolah:

- [ ] Hardware tested di meja: tap kartu → response benar
- [ ] WiFi credentials di firmware sudah benar
- [ ] API URL menggunakan HTTPS (bukan HTTP)
- [ ] API token sudah di-set dengan benar (60 karakter)
- [ ] Device kode unik per reader
- [ ] Sudah test scenario: kartu valid, kartu tidak terdaftar, kartu nonaktif, double tap
- [ ] Pengaturan jam masuk + batas terlambat sudah set di Filament
- [ ] Minimal 5 kartu siswa sudah didaftarkan untuk uji coba
- [ ] Casing tertutup rapat, tahan dari tumpahan air
- [ ] Power adapter terpasang aman (gunakan kotak terminal kalau perlu)
- [ ] Petugas piket sudah dijelaskan cara cek `Monitor Gerbang` di Filament
- [ ] Petugas piket sudah dijelaskan cara input manual presensi (kalau reader rusak)

---

## 11. Maintenance & Operasional

### Harian
- Petugas piket cek **Monitor Gerbang** di Filament untuk memastikan semua siswa sudah tap
- Kalau ada siswa yang tidak tap (lupa kartu), input manual via `Presensi Harian → New`

### Mingguan
- Cek **Pengaturan → Log Scan RFID** filter "Hanya Gagal" — kalau ada kartu yang sering ditolak, follow up dengan siswa
- Cek device `terakhir_aktif` di **RFID Device** — kalau lama tidak update, device bermasalah

### Bulanan
- Backup database (sudah otomatis kalau pakai script deploy)
- Update firmware ESP32 kalau ada perbaikan

### Tahunan
- Ganti kartu siswa kelas 12 yang lulus → tandai status `nonaktif`
- Daftarkan kartu siswa kelas 7 baru

---

## 12. Referensi & Resources

- [ESP32 Pinout Reference](https://randomnerdtutorials.com/esp32-pinout-reference-gpios/)
- [MFRC522 Library Documentation](https://github.com/miguelbalboa/rfid)
- [ArduinoJson v6 Tutorial](https://arduinojson.org/v6/example/)
- [Adafruit SSD1306 OLED](https://learn.adafruit.com/monochrome-oled-breakouts/arduino-library-and-examples)

---

## 13. FAQ

**Q: Bisa pakai NodeMCU ESP8266?**
A: Bisa, tapi RAM lebih kecil dan SSL HTTPS support terbatas. Rekomendasi tetap ESP32.

**Q: Kalau internet sekolah down, presensi gagal?**
A: Iya. Solusi: petugas piket input manual via Filament setelah internet kembali. Atau next phase, bisa tambah local storage di SD card untuk buffer offline.

**Q: 1 kartu hilang, gimana?**
A: Tata Usaha → **Kartu RFID** → cari kartu siswa → action **Tandai Hilang**. Buat kartu baru dengan UID berbeda. Sistem otomatis nonaktifkan kartu lama saat kartu baru aktif.

**Q: Berapa kartu yang bisa ditampung sistem?**
A: Tidak ada limit hard. Database mampu handle ratusan ribu kartu. Bottleneck di RAM ESP32, tapi karena lookup di server (bukan ESP32), tidak masalah.

**Q: Apakah boleh 1 reader untuk 2 sekolah?**
A: Tidak. Setiap reader punya 1 token yang terikat ke 1 instalasi sistem. Kalau 2 sekolah, 2 instalasi terpisah.

**Q: Bisa integrasikan dengan sistem absensi pelajaran (per-mata pelajaran)?**
A: Saat ini independent by design. Tapi report bisa cross-check kedua sumber data di dashboard.


---

## 14. Pakai HTTP IP+Port (Tanpa Domain)

Kalau VPS belum punya domain, bisa langsung pakai IP + port. **HTTPClient library otomatis detect HTTP vs HTTPS dari URL prefix** — tidak perlu modifikasi firmware lain selain URL.

### Format URL

```cpp
// Sebelum (HTTPS dengan domain):
const char* API_URL = "https://sekolah.example.com/api/rfid/scan";

// Sesudah (HTTP dengan IP+port):
const char* API_URL = "http://203.0.113.45:8000/api/rfid/scan";
```

Sesuaikan IP dan port dengan yang aktual di VPS-mu.

### Verifikasi Sebelum Upload Firmware

Cek dari laptop yang join WiFi sekolah:

```bash
curl -X POST http://VPS_IP:PORT/api/rfid/scan \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -d '{"uid":"04A1B2C3","scanned_at":"2026-05-24T07:00:00+07:00"}'
```

Kalau response 200/401 OK (server respond, walau token salah), berarti reachable. Kalau timeout, cek firewall VPS.

### ⚠️ Trade-off Keamanan

| Aspek | HTTPS Domain | HTTP IP+Port |
|---|---|---|
| API token | Encrypted in transit | **Plain text — bisa di-sniff** |
| Setup effort | Perlu domain + cert | Langsung pakai |
| Cocok untuk | Production | Dev / testing / staging awal |
| Risk kalau WiFi sekolah open | Low | **Tinggi** (token bisa dicuri) |
| Risk kalau WiFi WPA2 password kuat | Low | Medium |

### Migrasi dari HTTP ke HTTPS Nanti

Saat sudah punya domain, tinggal:
1. Setup Caddy/Nginx + Let's Encrypt cert (5 menit)
2. Edit firmware: ganti `http://IP:PORT/...` ke `https://domain/...`
3. Upload ulang firmware ke setiap reader
4. Token tidak perlu di-regenerate — backend tetap sama

### Setup HTTPS Tercepat dengan Caddy

```caddy
# /etc/caddy/Caddyfile
sekolah.example.com {
    reverse_proxy 127.0.0.1:8000
}
```

```bash
sudo systemctl reload caddy
```

Caddy auto generate Let's Encrypt cert dan handle renewal otomatis. Tidak perlu config tambahan.

