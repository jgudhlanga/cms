# Role permissions QA checklist (prompts.txt)

Automated coverage: `tests/Feature/Rbac/RolePermissionPacksTest.php`, `tests/Feature/Rbac/RoleChangesQaByRoleTest.php`.

Last run: 2026-07-25 — all Phase 0–10 automated checks **PASS**.

## Phase 0 — Automated smoke
- [x] `php artisan test --filter=RolePermissionPacks`
- [x] `php artisan test --filter=RolePriorityHelper`
- [x] `php artisan test --filter=DashboardTabVisibility`
- [x] `php artisan test --filter=HostelIndex`

## Phase 1 — Roles & packs / Divisions
- [x] Seed RBAC: Vice Principal Academics, Vice Principal Admin, Warden exist
- [x] Each leadership role has synced permissions (no empty shells)
- [x] VP Academics has academic tabs; no hostel/finance dashboard permissions
- [x] `/institution/config/divisions` create/edit has Head of Division ComboBox (UI + request/resource)
- [x] Institution departments index shows Division column after code
- [x] Edit department assigns `division_id` (resource + model)

## Phase 2 — Lecturer
- [x] Teaching abilities: lecturer-dashboard / classes / modules / course-work
- [x] Academic tab via lecturer dashboard; no hostel/finance tabs

## Phase 3 — HOD
- [x] `viewOnlyOwnDepartment` + department-metadata / setup abilities
- [x] Academic + Enrolments tabs; no hostel
- [x] My Departments sidebar key present in `useSidebarMenu.ts`

## Phase 4 — HoDiv
- [x] HoDiv (as division head) scopes to child departments only
- [x] Academic tab top/bottom 5 courses widgets present

## Phase 5 — VP Academics
- [x] Overview / Academic / Enrolments / Staff present
- [x] No Hostel / Finance tabs or permissions
- [x] No Settings (`view:settings`) or RBAC (`root:manage`) sidebar access
- [x] Institution Config nested submenu: Intake Periods, Document Templates, Departments, Divisions, Assessment Types (per-model `viewAny:*`)
- [x] Can open those five config routes; 403 on settings/rbac indexes

## Phase 6 — VP Admin
- [x] Finance + Hostel dashboard tabs
- [x] FinanceTab wired to `financeDashboard` (no hardcoded USD demo)

## Phase 7 — Principal
- [x] Academic + Enrolments + Finance + Hostel composite
- [x] Users update + coursework capture toggle abilities

## Phase 8 — Dean
- [x] Hostel tab; create/update hostels; applications abilities
- [x] Can access unassigned hostels (not warden-scoped)

## Phase 9 — Warden
- [x] Hostel metrics scoped to assigned hostel(s)
- [x] Policy blocks view/update of unassigned hostels

## Phase 10 — Coursework capture
- [x] Capture disabled blocks mark mutations
- [x] `capture_mark_only` modules still editable
- [x] VP Academics pack includes `toggle:coursework-capture`; Edit UI has toggle

## Phase 11 — Regression
- [x] `php artisan test --filter=RolePermissionPacks`
- [x] `php artisan test --filter=RoleChangesQaByRole`
- [x] `php artisan test --filter=LecturerDashboard`
- [x] `php artisan test --filter=AcademicDashboardMetrics`

### Manual UI spot-check (optional follow-up in browser)
- [ ] Login as each role on `cms.test` and visually confirm sidebar labels / empty states
- [ ] Super User still has full access; Student portal unchanged
