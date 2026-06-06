<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# routes

## Purpose
Route definitions. The app is mostly Filament (routes auto-registered by the panel), so these files are minimal.

## Key Files
| File | Description |
|------|-------------|
| `api.php` | `POST /api/rfid/scan` → `RfidScanController@store`, behind the `rfid.device` middleware group. Named `api.rfid.scan` |
| `web.php` | Minimal — the Filament `auth` panel registers its own routes at `/auth` |
| `console.php` | Artisan closure commands / scheduling |

## For AI Agents

### Working In This Directory
- API prefix `api` and route file wiring are set in `bootstrap/app.php` (`withRouting`), along with the `rfid.device` middleware alias.
- Filament resource/page routes are **not** here — they come from the panel provider's discovery.
- Reference routes by name (`route('api.rfid.scan')`).
- Health check is exposed at `/up` (configured in `bootstrap/app.php`).

### Testing Requirements
- API routes covered by `tests/Feature/Api/`.

## Dependencies

### Internal
- `app/Http/Controllers/Api/RfidScanController`, `app/Http/Middleware/AuthenticateRfidDevice`, `bootstrap/app.php`

<!-- MANUAL: -->
