<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Http

## Purpose
The only stateful HTTP surface outside Filament: the **RFID scan API** consumed by hardware readers (ESP32 + RC522). Everything else in the app is served by the Filament panel (Livewire), so this folder is intentionally small.

## Key Files
| File | Description |
|------|-------------|
| `Controllers/Controller.php` | Base controller |
| `Controllers/Api/RfidScanController.php` | `POST /api/rfid/scan` — delegates to `PresensiScanService`; carries full OpenAPI (l5-swagger) attribute docs |
| `Middleware/AuthenticateRfidDevice.php` | Bearer-token auth for readers; resolves an active `RfidDevice` via `verifyToken()` and stashes it on `request->attributes` as `rfid_device`. Alias `rfid.device` (registered in `bootstrap/app.php`) |
| `Requests/RfidScanRequest.php` | Validates the scan payload (`uid` required, optional `scanned_at`, `device_kode`) |

## For AI Agents

### Working In This Directory
- API auth is **device token**, not Sanctum — a reader sends `Authorization: Bearer <token>`; middleware checks all active devices' `verifyToken()`. A 401 returns `{success, message, reason}`.
- The controller is thin: parse `scanned_at`, hand off to `PresensiScanService::handle()`, return its array as JSON. Keep tap logic in the service.
- OpenAPI lives in PHP attributes (`OpenApi\Attributes`) on the controller — update them when changing request/response shape, then regenerate swagger.
- Validation rules go in Form Requests (per `CLAUDE.md`), never inline.

### Testing Requirements
- API behavior is covered by `tests/Feature/Api/RfidScanApiTest.php`; middleware/service edge cases in unit tests.

### Common Patterns
- JSON responses are Indonesian-messaged (`pesan`) and keyed by `jenis` (masuk/pulang/duplikat/ditolak/tidak_dikenal).

## Dependencies

### Internal
- `app/Services/Rfid/PresensiScanService`, `app/Models/RfidDevice`

### External
- `darkaonline/l5-swagger` (OpenAPI generation)

<!-- MANUAL: -->
