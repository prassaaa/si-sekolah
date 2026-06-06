<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Providers

## Purpose
Service providers. Registered in `bootstrap/providers.php` (Laravel 12). The important one is the Filament panel provider.

## Key Files
| File | Description |
|------|-------------|
| `AppServiceProvider.php` | Default app provider — currently empty `register()`/`boot()` |
| `Filament/AuthPanelProvider.php` | The single Filament panel (`auth`): id/path `auth`, `login()`, green primary, brand logo, sidebar collapsible, global search, user menu in sidebar, `profile()`. Auto-discovers Resources/Pages/Widgets. Registers `FilamentShieldPlugin` and the standard Filament middleware stack |

## For AI Agents

### Working In This Directory
- Panel-wide UI/behavior (colors, logo, sidebar, search, plugins, middleware, default page) is configured in `AuthPanelProvider::panel()` — change it here, not in individual resources.
- This is a **single-panel** app (path `/auth`). Adding a second panel means a new PanelProvider here + registration in `bootstrap/providers.php`.
- Global bindings/macros/observers go in `AppServiceProvider::boot()`.

### Testing Requirements
- Smoke-test the panel loads and shield gates resources via Feature tests.

### Common Patterns
- Discovery-based registration (`discoverResources/Pages/Widgets`) — no manual class lists.

## Dependencies

### Internal
- `app/Filament/**` (discovered), `bootstrap/providers.php` (registration)

### External
- `filament/filament`, `bezhansalleh/filament-shield`

<!-- MANUAL: -->
