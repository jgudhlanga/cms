# Production workbook: Enrolment requirements migration

**Feature:** Institution Config → **Enrolment setup** → Requirements & Class sizes  
**Purpose:** Academic entry requirements (`application_*_requirements`) and class-size config UI move off department Setup.  
**Risk level:** High — Phase B **drops** legacy requirement tables after soak.

Use with [deployment-enrolment-setup-production.md](deployment-enrolment-setup-production.md).

---

## Phase A (feature deploy — reversible)

### Migrations

1. `2026_08_24_100000_create_application_requirement_tables` — creates tables, snapshots, backfills from legacy.
2. App reads/writes `application_level_requirements` / `application_course_requirements`.
3. Legacy tables remain (frozen).

### Commands

| Command | When |
|---------|------|
| `php artisan enrolments:backfill-requirements --dry-run` | Before migrate on staging clone |
| `php artisan enrolments:backfill-requirements` | Re-run if needed while legacy tables exist |
| `php artisan enrolments:restore-requirements` | Phase A rollback: copy new → legacy before code rollback |

### Phase A rollback

1. Maintenance mode.
2. `php artisan enrolments:restore-requirements`
3. Deploy previous app release.
4. Smoke guest apply + staff enrolment lists.

---

## Phase B (after 24–72h soak)

### Migration

`2026_08_24_100100_drop_legacy_requirement_tables` — snapshot, drop `department_level_requirements` and `course_requirements`.

### Phase B rollback

1. `php artisan migrate:rollback` (recreates legacy tables + restores latest snapshot).
2. Deploy previous app release.
3. If snapshot missing: **restore DB backup** from before Phase B.

---

## Smoke tests (Phase A)

- [ ] `/institution/enrolments/{id}` shows Offerings / Requirements / Class sizes tabs.
- [ ] Department Setup no longer shows requirements columns or class-size grid.
- [ ] Portal Add programme still loads requirements API.
- [ ] Guest apply validates O-level / SDP acknowledgements.
- [ ] Staff CourseLevelEnrolments shows grades when level requires O-level.
- [ ] Classes modes still save on department Setup.
- [ ] Class-size inline editor on enrolment lists still works (`department-setup:class-sizes`).

## Baseline capture (before migrate)

Save JSON from:

- `GET /api/v1/institution-departments/levels/{department_level}/requirements`
- `GET /api/v1/institution-departments/{department_level}/courses/{department_course}/requirements`

For 2–3 known programmes (include a course with `has_enrolment_requirements`).

---

## Sign-off

| Step | Done (UTC) | Notes |
|------|------------|-------|
| Staging dry-run | | |
| Phase A migrate | | |
| Phase A smoke | | |
| Soak complete | | |
| Phase B migrate | | |

**Release SHA:** _______________
