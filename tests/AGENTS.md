<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# tests

## Purpose
Pest v4 test suite. Coverage currently centers on the RFID/presensi subsystem (its logic is the riskiest, non-CRUD part). CRUD resources are mostly exercised via the few resource Feature tests.

## Key Files
| File | Description |
|------|-------------|
| `Pest.php` | Pest bootstrap — binds `TestCase` and traits (e.g. `RefreshDatabase`) to suites |
| `TestCase.php` | Base test case |
| `Feature/*ResourceTest.php` | Filament resource tests: `KartuRfid`, `RfidDevice`, `RfidScanLog`, `PresensiHarian`, `PresensiHarianPegawai`, `Absensi` |
| `Feature/Api/RfidScanApiTest.php` | End-to-end RFID scan API (auth, validation, masuk/pulang/duplikat) |
| `Unit/*ModelTest.php` | Model tests: `KartuRfid`, `PresensiHarian`, `RfidDevice` |
| `Unit/PresensiScanServiceTest.php` | Core tap pipeline branches |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Feature/` | HTTP/Livewire/resource tests (most tests live here) |
| `Feature/Api/` | RFID JSON API tests |
| `Unit/` | Model + service unit tests |

## For AI Agents

### Working In This Directory
- All tests are **Pest**. Create with `php artisan make:test --pest Name` (`--unit` for unit). Do not delete existing tests without approval.
- Use model factories and their states; use `RefreshDatabase` for DB-touching tests.
- Run focused: `php artisan test --compact --filter=Name`. Use specific assertions (`assertForbidden`, `assertNotFound`) over `assertStatus`.
- Filament: test pages with Livewire helpers (`livewire(ListSiswas::class)->assertCanSeeTableRecords(...)`).

### Common Patterns
- Datasets for validation-rule tables; `Event::fake()`/`actingAs()` available in browser tests too.

## Dependencies

### Internal
- `database/factories/`, `app/**`

### External
- `pestphp/pest` v4, `pestphp/pest-plugin-laravel`, `mockery/mockery`

<!-- MANUAL: -->
