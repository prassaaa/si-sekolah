<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Resources

## Purpose
~50 Filament CRUD resources, one folder per domain entity (Siswa, Pegawai, Pembayaran, KartuRfid, RfidDevice, JurnalUmum, etc.). Every folder follows an identical structure, so this file documents the **shared convention** rather than each resource.

## Per-Resource Structure
Each `XxxResource/` folder contains:

| Item | Description |
|------|-------------|
| `XxxResource.php` | Resource class: `$model`, `$navigationIcon`, `$navigationGroup`, `$navigationLabel`, `$navigationSort`, `$recordTitleAttribute`; wires up form/infolist/table/pages |
| `Pages/` | `ListXxx`, `CreateXxx`, `EditXxx`, `ViewXxx` page classes |
| `Schemas/` | `XxxForm` (create/edit schema) and `XxxInfolist` (view schema) |
| `Tables/` | `XxxsTable` — column/filter/action config for the list view |

Example resource header (`Siswas/SiswaResource.php`):
```php
protected static ?string $model = Siswa::class;
protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';
protected static ?string $navigationLabel = 'Siswa';
public static function form(Schema $schema): Schema { return SiswaForm::configure($schema); }
public static function table(Table $table): Table { return SiswasTable::configure($table); }
```

## Navigation Groups
Resources are bucketed into the menu groups defined in `project.md`: **Kesiswaan, Kepegawaian, Akademik, Keuangan, Akuntansi, Pengaturan**. RFID resources (KartuRfid, RfidDevice, RfidScanLog) sit under settings/monitoring.

## For AI Agents

### Working In This Directory
- Scaffold new resources with `php artisan make:filament-resource Xxx --no-interaction` (v4 generates the folder/Schemas/Tables/Pages split), then mirror a sibling.
- Keep form/table logic in `Schemas/` and `Tables/` — do not inline it in the Resource class.
- Set `$navigationGroup` to an existing group; don't invent new ones without checking `project.md`.
- Authorization is automatic via the matching `app/Policies/XxxPolicy.php` (shield).

### Testing Requirements
- Feature tests per resource in `tests/Feature` (see `KartuRfidResourceTest`, `RfidDeviceResourceTest` for the pattern).

### Common Patterns
- Indonesian labels, green primary theme inherited from the panel.
- `$recordTitleAttribute` set to the human name field (e.g. `nama`) for global search.

## Dependencies

### Internal
- `app/Models/` (one model per resource), `app/Policies/` (one policy per resource)

<!-- MANUAL: -->
