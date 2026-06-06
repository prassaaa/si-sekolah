<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# docs

## Purpose
Operator/hardware documentation (human-facing markdown).

## Key Files
| File | Description |
|------|-------------|
| `deployment-vps.md` | VPS deployment guide (~19KB) |
| `esp32-rfid-implementation.md` | ESP32 + RC522 reader firmware/integration guide for the `POST /api/rfid/scan` endpoint (~25KB) — API URL config, bearer token, HTTP IP+port usage |

## For AI Agents

### Working In This Directory
- These describe the hardware/deployment side of the RFID system; keep `esp32-rfid-implementation.md` in sync with the API contract in `app/Http/Controllers/Api/RfidScanController.php` (OpenAPI attributes) and `PresensiScanService`.
- Per `CLAUDE.md`, only create new doc files when the user explicitly asks.

## Dependencies

### Internal
- Mirrors the RFID API in `app/Http/` and `app/Services/Rfid/`

<!-- MANUAL: -->
