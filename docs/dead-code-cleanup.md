# Dead Code Cleanup Checklist

Repeatable inventory for finding unused pages, components, and PHP classes.
Do **not** delete `resources/js/components/ui/*` (shadcn kit) without a dedicated review.

## Inventory steps

1. **Inertia pages**
   - Collect page strings from `Inertia::render(...)` / `inertia(...)`.
   - Diff against files under `resources/js/pages`.
   - Flag path mismatches (render string ≠ file path) separately from unused pages.
   - Watch for dynamic page names (e.g. payment status helpers).

2. **Vue components (non-`ui/`)**
   - List SFCs under `resources/js/components` excluding `ui/`.
   - Search for imports **and** auto-imported PascalCase / kebab tags in templates.
   - Prefer deleting domain leftovers over generic form kits when uncertain.

3. **PHP**
   - Find classes under `app/Services`, `app/Http/Controllers`, `app/Http/Requests`, `app/Helpers` with no references outside their own file.
   - Always check `routes/` for controllers (class references may only appear in route files).
   - Do not treat policies as unused based on missing class-name greps (Gate discovery).

4. **Symbols in kept files**
   - Dead lang keys, `APP_MODULE_KEYS` entries, barrel re-exports, deprecated type aliases.

## Removal rules

- One theme per PR (rename leftovers, portal pages, core buttons, PHP, etc.).
- Re-verify with grep immediately before delete.
- After frontend deletes: regenerate or clean `resources/js/types/components.d.ts`, run `npm run lint`.
- After PHP / Inertia fixes: run targeted Pest tests.
- Keep unfinished-but-routed work (e.g. ComingSoon pages that are still rendered).

## Optional detectors (follow-up)

- JS unused files: Knip
- PHP unused code: PHPStan + dead-code rules / Larastan

These are optional; the checklist above is enough for a manual pass.
