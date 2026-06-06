<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Policies

## Purpose
~45 authorization policies, one per model, generated and driven by **filament-shield**. They gate every Filament resource action (viewAny, view, create, update, delete, restore, forceDelete) against shield-managed roles/permissions.

## Key Files
One `XxxPolicy.php` per model — e.g. `SiswaPolicy`, `PegawaiPolicy`, `PembayaranPolicy`, `JurnalUmumPolicy`, `KartuRfidPolicy`, `RfidDevicePolicy`, `RolePolicy`, `UserPolicy`, `ActivityPolicy`.

## For AI Agents

### Working In This Directory
- Policies are **shield-generated** — regenerate with `php artisan shield:generate` rather than hand-writing, then customize if needed.
- When you add a model + resource, add the matching policy so the resource is access-controlled (otherwise actions may be denied or open depending on config).
- Permission strings follow shield's `{ability}_{resource}` convention; roles are seeded by `database/seeders/RoleSeeder.php`.

### Testing Requirements
- Authorization is best covered through resource Feature tests acting as users with/without the permission.

### Common Patterns
- Each policy method checks the user's shield permission for that ability + resource.

## Dependencies

### Internal
- `app/Models/` (one policy per model), `database/seeders/RoleSeeder.php`

### External
- `bezhansalleh/filament-shield`

<!-- MANUAL: -->
