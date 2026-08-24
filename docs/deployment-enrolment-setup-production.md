# Production workbook: Enrolment setup (application offerings catalogue)

**Feature:** Institution Config → **Enrolment setup** (`/institution/enrolments`)  
**Purpose:** Online applications read programmes from `application_offering_*` tables instead of department flag columns.  
**Risk level:** High — this release **drops** legacy columns after auto-backfill. Cheap flag rollback is **not** available after migrate succeeds.

Use this as a checklist. Tick every box. Do not skip staging rehearsal.

---

## 0. Owners and freeze

| Role | Name | Contact |
|------|------|---------|
| Deploy owner | | |
| DB backup owner | | |
| Smoke-test owner (guest + portal) | | |
| Enrolment setup editor (Super User) | | |

- [ ] Registration / applications traffic window agreed (prefer low traffic or short maintenance).
- [ ] Stakeholders know programme trees may briefly empty if backfill fails (mitigate with backup + staging).
- [ ] Rollback owner named and reachable during deploy.

---

## 1. What this release does

### Migrations (order matters)

1. `2026_08_23_210000_create_application_offering_tables` — creates catalogue tables.
2. `2026_08_23_210100_grant_manage_enrolments_permission` — ensures `manage:online-application-catalogue` and grants **Super User** only.
3. `2026_08_23_211000_drop_legacy_application_offering_flag_columns` — **backfills offerings from old flags**, then drops:
   - `department_levels.show_on_current_application_period`
   - `department_courses.show_on_current_application_period`
   - `institution_departments.has_apprentice_courses`
4. `2026_08_23_220000_rename_manage_enrolments_to_online_application_catalogue` — renames legacy ability if present; Super User only.

### App behaviour after deploy

- Guest / portal programme trees → `application_offering_*` only.
- Add programme mode picker → `v1.enrolments.course-modes` (not Classes `course_level_modes`).
- Config UI → `/institution/enrolments` (route names `application-offerings.*`).
- Ability → `manage:online-application-catalogue` (Enrolments module).
- `levels.show_on_current_application_period` **unchanged** (institution-wide open qualifications).
- Classes / `course_level_modes` **unchanged**.

### Commands (post-deploy usefulness)

| Command | When to use |
|---------|-------------|
| `php artisan enrolments:backfill-offerings --dry-run` | **Only while legacy columns still exist** (before / during migration). After drop, it exits with an error by design. |
| `php artisan enrolments:restore-flags-from-offerings` | **Only if legacy columns still exist**. After drop, fails by design. |
| Seeders | Prefer migrations for permission; optional reseed if Super User missing ability. |

---

## 2. Pre-production (staging) — mandatory

### 2.1 Snapshot staging from production-like data

- [ ] Staging DB restored from a recent production dump (or close enough for department/level/course/mode flags).
- [ ] Note open intakes and a few known programmes (include LIS / Ojet-only case if relevant).

### 2.2 Capture baseline programme trees (before migrate)

With **current production code** (or pre-release) on staging:

- [ ] Regular track programme tree for NC (or primary open level) — screenshot / API JSON saved.
- [ ] Apprentice track for same level.
- [ ] Continuous SDP / Ojet if used.
- [ ] Spot-check department IDs and course/mode counts.

API example (adjust host/auth as you use):

```bash
# Guest programmes (no staff auth)
curl -sS "$APP_URL/api/v1/guest/enrollment/programmes?track=regular&level_id=LEVEL_ID" | jq .
```

Record:

| Check | Level ID | Available? | Dept count | Notes |
|-------|----------|------------|------------|-------|
| Regular | | | | |
| Apprentice | | | | |
| Continuous SDP | | | | |
| Continuous Ojet | | | | |

### 2.3 Dry-run backfill **before** column drop (staging)

Deploy **only** offering-table migration first if you split deploys; otherwise on a DB clone with columns still present:

```bash
php artisan enrolments:backfill-offerings --dry-run
```

- [ ] Dry-run counts look sane vs baseline trees (departments / levels / courses / modes).
- [ ] Document dry-run output in this workbook or ticket.

### 2.4 Full staging migrate + app

```bash
# After code deploy on staging
php artisan down --retry=60   # optional
php artisan migrate --force
php artisan permission:cache-reset
php artisan config:cache
php artisan route:cache
php artisan view:cache
# build frontend assets as per your usual pipeline
php artisan up
```

- [ ] Migrations all `DONE`.
- [ ] Snapshot file created under `storage/app/enrolments/backfill-*.json` (from drop migration backfill).
- [ ] Copy snapshot off the box (S3 / secure share).

### 2.5 Staging verification

- [ ] Super User sees **Institution Config → Enrolment setup**.
- [ ] `/institution/enrolments` lists academic departments with colours.
- [ ] Configure one department; save; reload; selections persist.
- [ ] Guest Regular tree matches baseline (spot-check LIS + a Full Time dept).
- [ ] Apprentice tree respects apprentice flag on offering department.
- [ ] Add programme (portal) modes come from offerings, not Classes-only modes.
- [ ] Classes / course level modes UI still works for teaching.
- [ ] Config → Levels “show on current application period” still works.

### 2.6 Staging failure drill (document results)

- [ ] Restore from backup taken before migrate works.
- [ ] Time to restore known: ______ minutes.

**Do not proceed to production until staging trees match baseline.**

---

## 3. Production pre-flight (same day)

### 3.1 Backup (non-negotiable)

- [ ] Full database backup taken **immediately before** migrate.
- [ ] Backup verified restorable (checksum / test restore on spare if available).
- [ ] Backup location: _______________________
- [ ] Backup timestamp: _______________________

Optional but recommended:

```bash
# Row counts before cutover
php artisan tinker --execute="
echo 'dept_levels on app: '.DB::table('department_levels')->where('show_on_current_application_period',1)->count().PHP_EOL;
echo 'dept_courses on app: '.DB::table('department_courses')->where('show_on_current_application_period',1)->count().PHP_EOL;
echo 'apprentice depts: '.DB::table('institution_departments')->where('has_apprentice_courses',1)->count().PHP_EOL;
"
```

(Skip if columns already gone on this environment.)

### 3.2 Code and assets ready

- [ ] Release tag / commit SHA: _______________________
- [ ] Frontend build included (Ziggy routes + Vue pages for catalogue).
- [ ] Queue workers / Horizon will be restarted after deploy.
- [ ] No pending unrelated migrations that could fail mid-run.

### 3.3 Access

- [ ] Super User account confirmed for Enrolment setup.
- [ ] Deploy SSH / CI access confirmed.
- [ ] `php artisan` works on app host with correct `.env`.

---

## 4. Production deploy sequence

Keep this short and linear. Prefer maintenance mode if applications are open.

```bash
# 1) Maintenance (recommended while registration open)
php artisan down --retry=60 --secret=YOUR_BYPASS_TOKEN

# 2) Deploy code (your git/CI process)
# …

# 3) Dependencies / assets (project-specific)
# composer install --no-dev --optimize-autoloader
# npm ci && npm run build   # or your CI artifact

# 4) Migrate — THIS BACKFILLS THEN DROPS LEGACY COLUMNS
php artisan migrate --force

# 5) Permissions cache + app caches
php artisan permission:cache-reset
php artisan optimize:clear   # or config/route/view cache as you normally do
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6) Restart PHP-FPM / Octane / Horizon / queues (as applicable)

# 7) Bring up
php artisan up
```

### During migrate — watch for

- [ ] `210000` create tables — OK  
- [ ] `210100` grant permission — OK  
- [ ] `211000` drop legacy — may take longer (backfill + snapshot + DDL)  
- [ ] `220000` rename permission — OK  

If `211000` fails mid-way:

1. **Do not** keep serving new code against a half-migrated DB.
2. Restore DB from pre-deploy backup.
3. Roll app to previous release.
4. Investigate on staging with the failed dump.

### Immediately after migrate

```bash
# Confirm legacy columns are gone
php artisan tinker --execute="
echo Schema::hasColumn('department_levels','show_on_current_application_period') ? 'levels flag STILL THERE' : 'levels flag dropped';
echo PHP_EOL;
echo Schema::hasColumn('department_courses','show_on_current_application_period') ? 'courses flag STILL THERE' : 'courses flag dropped';
echo PHP_EOL;
echo Schema::hasColumn('institution_departments','has_apprentice_courses') ? 'apprentice STILL THERE' : 'apprentice dropped';
echo PHP_EOL;
echo 'offering depts: '.DB::table('application_offering_departments')->whereNull('deleted_at')->count().PHP_EOL;
echo 'offering levels: '.DB::table('application_offering_levels')->whereNull('deleted_at')->count().PHP_EOL;
echo 'offering courses: '.DB::table('application_offering_courses')->whereNull('deleted_at')->count().PHP_EOL;
echo 'offering modes: '.DB::table('application_offering_modes')->whereNull('deleted_at')->count().PHP_EOL;
"

# Snapshot path
ls -la storage/app/enrolments/
```

- [ ] Offering counts > 0 (unless production truly had nothing offered).
- [ ] Snapshot copied off-box: _______________________

Optional Super User check:

```bash
php artisan tinker --execute="
\$role = App\Models\Rbac\Role::where('name','Super User')->first();
echo \$role && \$role->hasPermissionTo('manage:online-application-catalogue') ? 'Super User OK' : 'MISSING ABILITY';
"
```

If missing:

```bash
php artisan db:seed --class=Database\\Seeders\\Rbac\\PermissionsTableSeeder --force
php artisan db:seed --class=Database\\Seeders\\Rbac\\RolesTableSeeder --force
php artisan permission:cache-reset
```

(Super User must log out/in after ability change.)

---

## 5. Production smoke tests (within 15 minutes of `up`)

### 5.1 Staff UI

- [ ] Login as Super User.
- [ ] Sidebar: **Institution Config → Enrolment setup**.
- [ ] Index loads; department colours visible.
- [ ] Open a known department; offerings look correct.
- [ ] Save without change succeeds (flash saved).

### 5.2 Public / applicant trees

Compare to staging baseline / known production programmes:

- [ ] Guest Regular programmes for open level — not empty when they should not be.
- [ ] Apprentice programmes — departments without apprentice offering hidden.
- [ ] Continuous SDP / Ojet filters still correct if used.
- [ ] Portal Add programme: modes list for a known course/level matches Enrolment setup (not Classes-only).

### 5.3 Regression

- [ ] Department Setup → Courses → Course level modes still editable.
- [ ] Classes / enrolments class lists still load.
- [ ] Config → Levels show-on-current still toggles open qualifications.

### 5.4 Logs

- [ ] No spike of 500s on `institution/enrolments*`, guest programmes, or store-application.
- [ ] `storage/logs/laravel.log` clean for catalogue/undefined-variable issues.

---

## 6. If something is wrong

### 6.1 Trees empty or wrong after deploy (most common)

1. Confirm offering tables have rows (tinker counts above).
2. If empty: **restore DB backup**, redeploy previous app release, open incident — do not invent data on production without a plan.
3. If rows exist but UI/API empty: check SoftDeletes, tenant_id, and that institution levels still have `show_on_current_application_period = 1`.
4. Fix catalogue via **Enrolment setup** UI for small gaps (preferred over SQL).

### 6.2 Super User cannot see Enrolment setup

```bash
php artisan permission:cache-reset
# reseed permissions/roles if needed (see §4)
```

User must re-login.

### 6.3 Full rollback (Phase 5 already applied)

There is **no** `ENROLMENTS_USE_APPLICATION_OFFERINGS` flip anymore.

1. Put app in maintenance.
2. Restore **database** from pre-migrate backup.
3. Deploy **previous** application release (code that still uses flag columns).
4. Clear caches; bring up.
5. Verify old trees.
6. Schedule a new staging pass before retrying.

If you only roll code back but leave the new DB (columns dropped), applicants will break.

### 6.4 Commands after columns dropped

Expect:

```text
Legacy application flag columns have been removed…
```

That is correct. Do **not** rely on `enrolments:restore-flags-from-offerings` after this release.

---

## 7. Post-deploy soak (24–72 hours)

- [ ] Spot-check LIS / Ojet vs Full Time departments daily.
- [ ] Monitor application creation success rate.
- [ ] Staff use Enrolment setup for any intentional catalogue edits (do not re-introduce flag columns).
- [ ] Keep pre-deploy DB backup until soak ends (minimum 7 days recommended).

---

## 8. Sign-off

| Step | Owner | Done (UTC) | Notes |
|------|-------|------------|-------|
| Staging trees match baseline | | | |
| Production DB backup verified | | | |
| Migrate completed | | | |
| Snapshot archived | | | |
| Smoke tests passed | | | |
| Soak started | | | |

**Release SHA:** _______________________  
**Deployed by:** _______________________  
**Date (UTC):** _______________________

---

## Appendix A — Useful URLs

| What | Path / route |
|------|----------------|
| Enrolment setup index | `/institution/enrolments` → `application-offerings.index` |
| Configure department | `/institution/enrolments/{id}` → `application-offerings.show` |
| Application modes API | `GET /api/v1/enrolments/course-modes/{department_course}/course/{department_level}/level` |
| Classes modes API (unchanged) | `GET /api/v1/...` `v1.modes-of-study.course-modes` |

## Appendix B — Mental model

```
Department setup  →  what the department runs (levels, courses, course_level_modes)
Enrolment setup   →  what applicants can choose (application_offering_*)
Config → Levels   →  institution-wide “qualification is open”
```

Never assume Classes modes = application modes after this release.
