# Production deploy workbook: `development` → production

**From:** `main` @ `50e43e81` (`requirements fix`)  
**To:** `development` @ `026849d2` (`requirements`)  
**Delta:** 13 commits, ~368 files. `main` has no commits that `development` lacks.

**Risk:** **High.** This release backfills live enrolment data, **collapses duplicate** `student_enrolments`, **drops application-flag columns**, and (if you run every pending migration) **drops legacy requirement tables**.

Use this as the release checklist. Tick every box. Do not skip staging rehearsal.

Feature-specific detail:

- [deployment-enrolment-setup-production.md](deployment-enrolment-setup-production.md) — offerings catalogue
- [deployment-enrolment-requirements-production.md](deployment-enrolment-requirements-production.md) — requirements Phase A/B

---



## 0. Owners and freeze


| Role                                                     | Name | Contact |
| -------------------------------------------------------- | ---- | ------- |
| Deploy owner                                             |      |         |
| DB backup owner                                          |      |         |
| Smoke-test owner (guest + portal + enrolments + classes) |      |         |
| Enrolment setup editor (Super User)                      |      |         |


- [ ] Applications / registration window agreed (prefer maintenance while open).
- [ ] Stakeholders know programme trees can empty if offering backfill fails.
- [ ] Rollback owner reachable for the full window (migrations are not all cheap to undo).

**Do not run a blind** `php artisan migrate --force`**.** That executes **Phase B** (`2026_08_24_100100_drop_legacy_requirement_tables`) in the same batch as Phase A. Split migrations as in §4.

---



## 1. What this release contains


| Workstream                              | Risk   | Data change                                                                                                        |
| --------------------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------ |
| Student semesters pivot                 | High   | Creates `student_semesters` + rollback tables; **soft-deletes duplicate year/mode enrolments**; links class pivots |
| Department colour codes                 | Low    | Adds `institution_departments.color_code`, assigns unique hex per tenant                                           |
| Enrolment setup (offerings catalogue)   | High   | Creates `application_offering_*`; **backfills then drops** flag columns                                            |
| Enrolment requirements & class sizes UI | High   | Creates `application_*_requirements`; Phase B **drops** legacy tables                                              |
| Enrolment ops                           | Medium | Applicant lookup, bulk add/purge class lists, `confirm:class-lists`, verification/confirmation UI                  |
| Academic calendar classes               | Medium | Add / remove students on a class                                                                                   |
| Guest OJET former-student gate          | Medium | Registration flow for returning OJET students                                                                      |
| Users audit trail                       | Low    | Activity log moves off dashboard to `/users/audit-trail`                                                           |
| Examinations dashboard tab              | Low    | New `view-examinations:dashboards` ability (seeded, not migrated)                                                  |
| Enrolment status `Unknown`              | Low    | Seeder-only row                                                                                                    |


No `composer.json` / `package.json` lockfile changes in this delta. Frontend **must** still be rebuilt (Ziggy + Vue).

---



## 2. Migrations (chronological)

Pending on production relative to `main`:


| #   | File                                                                         | What `up()` does                                                                                                                      | Destructive?                   | `down()`                                                                          |
| --- | ---------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ | --------------------------------------------------------------------------------- |
| 1   | `2026_08_22_150000_create_student_semesters_and_rollback_tables`             | Creates `student_semesters`, rollback tables, nullable `academic_calendar_student_enrolments.student_semesters_id`                    | Schema only                    | Drops column + three tables                                                       |
| 2   | `2026_08_22_150100_backfill_student_semesters_data`                          | Runs `enrolments:backfill-student-semesters` (skipped in unit tests)                                                                  | **Yes** — collapses duplicates | Runs `enrolments:rollback-student-semesters`                                      |
| 3   | `2026_08_23_120000_add_color_code_to_institution_departments_table`          | Adds `color_code`, assigns palette colours                                                                                            | No                             | Drops column                                                                      |
| 4   | `2026_08_23_130000_ensure_unique_institution_department_color_codes`         | Re-assigns colliding colours                                                                                                          | Overwrites some codes          | **Empty** (no-op)                                                                 |
| 5   | `2026_08_23_210000_create_application_offering_tables`                       | Creates four `application_offering_*` tables                                                                                          | No                             | Drops those tables                                                                |
| 6   | `2026_08_23_210100_grant_manage_enrolments_permission`                       | Ensures `manage:online-application-catalogue` (or legacy `manage:enrolments`) and grants **Super User**                               | No                             | Empty                                                                             |
| 7   | `2026_08_23_211000_drop_legacy_application_offering_flag_columns`            | **Backfills offerings + JSON snapshot**, then drops flags                                                                             | **Yes — columns gone**         | Re-adds columns **default** `false` (does **not** restore values)                 |
| 8   | `2026_08_23_220000_rename_manage_enrolments_to_online_application_catalogue` | Renames/merges permission; **revokes from every role except Super User**                                                              | Access change                  | Empty                                                                             |
| 9   | `2026_08_24_100000_create_application_requirement_tables`                    | Creates `application_level_requirements` / `application_course_requirements`; backfills if empty; **fails migrate** on count mismatch | Copy                           | Drops new tables                                                                  |
| 10  | `2026_08_24_100100_drop_legacy_requirement_tables`                           | Snapshot, then **drops** `department_level_requirements` and `course_requirements`                                                    | **Yes — Phase B**              | Recreates legacy tables and restores from live `application_`* or latest snapshot |


**Columns dropped in #7:**

- `department_levels.show_on_current_application_period`
- `department_courses.show_on_current_application_period`
- `institution_departments.has_apprentice_courses`

**Tables dropped in #10 (Phase B only):**

- `department_level_requirements`
- `course_requirements`

---



## 3. Backfill and rollback commands

All of these are **new on** `development`. None exist on `main`.

### 3.1 Student semesters


| Command                                                       | When                                                      | Notes                                                                                                                                                                                                |
| ------------------------------------------------------------- | --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `php artisan enrolments:backfill-student-semesters --dry-run` | Staging clone **before** migrate; optional pre-flight     | Reports collapse + sync counts; no writes                                                                                                                                                            |
| `php artisan enrolments:backfill-student-semesters`           | Auto-run by migration `150100`. Re-run is **idempotent**  | Groups duplicates by `student_application_id + calendar year + mode_of_study_id`; keeps highest `id`; **soft-deletes** others; writes rollback rows; creates `student_semesters`; links class pivots |
| `php artisan enrolments:rollback-student-semesters --dry-run` | Before a real rollback                                    | Counts from rollback tables                                                                                                                                                                          |
| `php artisan enrolments:rollback-student-semesters`           | Immediate rollback of the pivot, **or** `150100` `down()` | Restores pivot `student_enrolment_id`, enrolment columns, un-collapses soft-deleted rows; **force-deletes all** `student_semesters`; **truncates** rollback tables                                   |


Rollback data lives in:

- `student_semester_rollback_enrolments`
- `student_semester_rollback_class_pivots`

After soak + new enrolments, those snapshots go stale. Prefer a **pre-migrate DB backup** over this command once production has been live on the new model.

### 3.2 Application offerings (legacy flags → catalogue)


| Command                                                                                        | When                                                                         | Notes                                                                          |
| ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `php artisan enrolments:backfill-offerings --dry-run`                                          | Staging clone **while flag columns still exist**                             | Fails after `211000` with “Legacy application flag columns have been removed…” |
| `php artisan enrolments:backfill-offerings`                                                    | Optional manual run; **also auto-run inside** `211000`                       | `--fresh` wipes offering rows first; `--no-snapshot` skips JSON                |
| `php artisan enrolments:restore-flags-from-offerings --dry-run`                                | Only if flag **columns still exist**                                         |                                                                                |
| `php artisan enrolments:restore-flags-from-offerings`                                          | Phase A offerings rollback **after** `211000` `down()` re-adds empty columns | Copies from live `application_offering_`*                                      |
| `php artisan enrolments:restore-flags-from-offerings --from-snapshot=/path/to/backfill-….json` | Same, from snapshot                                                          | Path may be absolute or under `storage/app`                                    |


**Snapshot path:** `storage/app/enrolments/backfill-{YmdHis}.json`  
Written automatically during `211000` (unless columns were already gone).

### 3.3 Application requirements (legacy tables → enrolment setup)


| Command                                                                                             | When                                                                                        | Notes                                                                      |
| --------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| `php artisan enrolments:backfill-requirements --dry-run`                                            | Staging clone **while legacy tables exist**                                                 | Fails after Phase B                                                        |
| `php artisan enrolments:backfill-requirements`                                                      | Re-run while legacy tables exist; **also auto-run in** `100000` **if new tables are empty** | `--fresh` force-deletes application rows first; `--no-snapshot` skips JSON |
| `php artisan enrolments:restore-requirements --dry-run`                                             | Phase A rollback (legacy tables still present)                                              |                                                                            |
| `php artisan enrolments:restore-requirements`                                                       | Copy `application_*` → legacy tables                                                        | Needs `department_level_requirements` + `course_requirements`              |
| `php artisan enrolments:restore-requirements --from-snapshot=/path/to/requirements-backfill-….json` | Restore legacy from a file                                                                  |                                                                            |


**Snapshot path:** `storage/app/enrolments/requirements-backfill-{Ymd_His}.json`  
`100000` writes one during backfill. `100100` writes another immediately before drop.

### 3.4 Not part of this delta (already on `main`)

`departments:backfill-is-academic` — do not treat as a release step.

---



## 4. Phased production sequence

Treat this as **three migrate windows**. Code deploy is once (this `development` SHA); schema is gated with `--path`.

### Window 0 — Pre-flight (same day)

- [ ] Full DB backup, verified restorable. Location: __________ Timestamp: __________
- [ ] Release SHA: `026849d2` (or the SHA you actually ship)
- [ ] Frontend build included
- [ ] Queue / Horizon / PHP-FPM restart plan
- [ ] Super User account for Enrolment setup

**Baseline counts (old code, before migrate)** — dry-runs need the **new** artisan commands, so run them on a staging clone that already has this release code against a production-like DB **with legacy columns/tables still present**:

```bash
php artisan enrolments:backfill-student-semesters --dry-run
php artisan enrolments:backfill-offerings --dry-run
php artisan enrolments:backfill-requirements --dry-run
```

Save guest programme trees and a few requirement API payloads (see feature workbooks).

**Optional row counts (production, old code — columns still present):**

```bash
php artisan tinker --execute="
echo 'levels on app: '.DB::table('department_levels')->where('show_on_current_application_period',1)->count().PHP_EOL;
echo 'courses on app: '.DB::table('department_courses')->where('show_on_current_application_period',1)->count().PHP_EOL;
echo 'apprentice depts: '.DB::table('institution_departments')->where('has_apprentice_courses',1)->count().PHP_EOL;
echo 'level reqs: '.DB::table('department_level_requirements')->whereNull('deleted_at')->count().PHP_EOL;
echo 'course reqs: '.DB::table('course_requirements')->whereNull('deleted_at')->count().PHP_EOL;
echo 'enrolments: '.DB::table('student_enrolments')->whereNull('deleted_at')->count().PHP_EOL;
"
```

Capture guest trees:

```bash
curl -sS "$APP_URL/api/v1/guest/enrollment/programmes?track=regular&level_id=LEVEL_ID" | jq .
```



### Window 1 — Code + reversible / auto-backfill schema (maintenance recommended)

```bash
php artisan down --retry=60 --secret=YOUR_BYPASS_TOKEN

# deploy this release (git / CI)
# composer install --no-dev --optimize-autoloader
# npm ci && npm run build   # or your artifact

# Student semesters + colours + offerings tables + permission grant
php artisan migrate --force --path=database/migrations/2026_08_22_150000_create_student_semesters_and_rollback_tables.php
php artisan migrate --force --path=database/migrations/2026_08_22_150100_backfill_student_semesters_data.php
php artisan migrate --force --path=database/migrations/2026_08_23_120000_add_color_code_to_institution_departments_table.php
php artisan migrate --force --path=database/migrations/2026_08_23_130000_ensure_unique_institution_department_color_codes.php
php artisan migrate --force --path=database/migrations/2026_08_23_210000_create_application_offering_tables.php
php artisan migrate --force --path=database/migrations/2026_08_23_210100_grant_manage_enrolments_permission.php
```

**Stop. Confirm semester backfill before dropping flags.**

```bash
php artisan tinker --execute="
echo 'student_semesters: '.DB::table('student_semesters')->whereNull('deleted_at')->count().PHP_EOL;
echo 'rollback enrolments: '.DB::table('student_semester_rollback_enrolments')->count().PHP_EOL;
"
```

Then the **irreversible-without-backup** offering cutover:

```bash
php artisan migrate --force --path=database/migrations/2026_08_23_211000_drop_legacy_application_offering_flag_columns.php
php artisan migrate --force --path=database/migrations/2026_08_23_220000_rename_manage_enrolments_to_online_application_catalogue.php
```

Requirements **Phase A only** (legacy tables stay):

```bash
php artisan migrate --force --path=database/migrations/2026_08_24_100000_create_application_requirement_tables.php
```

**Do not run** `100100` **yet.**

RBAC + caches (new abilities are **not** created by a dedicated migration except the catalogue permission):

```bash
php artisan db:seed --class=Database\\Seeders\\Rbac\\PermissionsTableSeeder --force
php artisan db:seed --class=Database\\Seeders\\Rbac\\RolesTableSeeder --force
php artisan db:seed --class=Database\\Seeders\\Students\\StudentEnrolmentStatusSeeder --force
php artisan permission:cache-reset
php artisan config:cache
php artisan route:cache
php artisan view:cache
# restart PHP-FPM / Octane / Horizon / queues
php artisan up
```

`RolesTableSeeder` **syncs** packed roles (VP Academics gets `confirm:class-lists` and `view-examinations:dashboards`; Super User gets the catalogue ability). Custom roles are untouched — grant those by hand if needed.

Copy snapshots off-box:

```bash
ls -la storage/app/enrolments/
```

Expect:

- `backfill-*.json` (offerings)
- `requirements-backfill-*.json` (requirements)



### Window 2 — Soak 24–72h (Phase A)

Keep the pre-deploy backup. Fix catalogue/requirements via **Institution Config → Enrolment setup**, not SQL.

### Window 3 — Phase B (after soak)

```bash
php artisan down --retry=60 --secret=YOUR_BYPASS_TOKEN
php artisan migrate --force --path=database/migrations/2026_08_24_100100_drop_legacy_requirement_tables.php
php artisan up
```

Archive the new `requirements-backfill-*.json` written just before the drop.

---



## 5. Post-migrate checks (Window 1)

```bash
php artisan tinker --execute="
echo Schema::hasColumn('department_levels','show_on_current_application_period') ? 'levels flag STILL THERE' : 'levels flag dropped';
echo PHP_EOL;
echo Schema::hasColumn('department_courses','show_on_current_application_period') ? 'courses flag STILL THERE' : 'courses flag dropped';
echo PHP_EOL;
echo Schema::hasColumn('institution_departments','has_apprentice_courses') ? 'apprentice STILL THERE' : 'apprentice dropped';
echo PHP_EOL;
echo Schema::hasTable('department_level_requirements') ? 'legacy req tables STILL THERE (expected until Phase B)' : 'legacy req tables DROPPED';
echo PHP_EOL;
echo 'offering depts: '.DB::table('application_offering_departments')->whereNull('deleted_at')->count().PHP_EOL;
echo 'offering levels: '.DB::table('application_offering_levels')->whereNull('deleted_at')->count().PHP_EOL;
echo 'app level reqs: '.DB::table('application_level_requirements')->whereNull('deleted_at')->count().PHP_EOL;
echo 'app course reqs: '.DB::table('application_course_requirements')->whereNull('deleted_at')->count().PHP_EOL;
"

\$role = App\Models\Rbac\Role::where('name','Super User')->first();
echo \$role && \$role->hasPermissionTo('manage:online-application-catalogue') ? 'Super User OK' : 'MISSING ABILITY';
```

- [ ] Offering counts > 0 unless production truly had nothing offered
- [ ] Requirement counts match dry-run (minus skipped orphans)
- [ ] Snapshots copied off-box: __________

---



## 6. Smoke tests (within 15 minutes of `up`)



### Enrolment setup

- [ ] Super User: **Institution Config → Enrolment setup** (`/institution/enrolments`)
- [ ] Department colours on index
- [ ] Configure one department; save; reload
- [ ] Tabs: Offerings / Requirements / Class sizes
- [ ] Department Setup **no longer** has requirement columns or class-size grid for applications



### Public / applicant

- [ ] Guest Regular tree matches baseline
- [ ] Apprentice tree respects offering apprentice flag
- [ ] Continuous SDP / OJET if used
- [ ] Portal Add programme modes from `v1.enrolments.course-modes`, not Classes-only
- [ ] Guest apply still validates O-level / SDP acknowledgements
- [ ] OJET former-student registration path (student-number gate)



### Staff enrolments

- [ ] `/enrolments` department distribution
- [ ] Applicant lookup (`/enrolments/applicant-lookup`)
- [ ] Verify vs confirm: `confirm:class-lists` required for verified lists
- [ ] Bulk add / purge class list
- [ ] CourseLevelEnrolments shows grades when O-level is required



### Classes / progression

- [ ] Academic calendar class: add students / remove students
- [ ] Class still loads; staffing still saves
- [ ] Student profile semester cards; status `Unknown` available after seeder
- [ ] Advance / complete level still works (now via `student_semesters`)



### Other UI

- [ ] Users → **Audit trail** (`/users/audit-trail`); not on dashboard activity tab
- [ ] VP Academics / Principal / VP Admin: Examinations dashboard tab
- [ ] Config → Levels “show on current application period” still works (institution-wide, **not** dropped)



### Logs

- [ ] No 500 spike on `institution/enrolments*`, guest programmes, store-application, class lists, academic calendar classes

---



## 7. Rollback scripts (by workstream)

Put the app in maintenance first. **Do not roll code back onto a half-migrated DB.**

### 7.1 Student semesters (Window 1, before soak)

```bash
php artisan enrolments:rollback-student-semesters --dry-run
php artisan enrolments:rollback-student-semesters
php artisan migrate --force --path=database/migrations/2026_08_22_150100_backfill_student_semesters_data.php --rollback
php artisan migrate --force --path=database/migrations/2026_08_22_150000_create_student_semesters_and_rollback_tables.php --rollback
```

(`150100` `down()` already calls the rollback command; running the artisan command first is optional but makes counts visible.)

After soak: **restore the pre-deploy DB backup** instead.

### 7.2 Department colours

```bash
php artisan migrate --force --path=database/migrations/2026_08_23_120000_add_color_code_to_institution_departments_table.php --rollback
```

`130000` `down()` is empty; rolling `120000` drops `color_code`. UI will look uncoloured; not a data-loss incident.

### 7.3 Offerings catalogue (after `211000`)

`enrolments:restore-flags-from-offerings` **fails** while columns are dropped. Sequence:

```bash
# 1) Re-add empty flag columns (values default false)
php artisan migrate --force --path=database/migrations/2026_08_23_211000_drop_legacy_application_offering_flag_columns.php --rollback

# 2) Copy flags back (live offerings OR snapshot taken during 211000)
php artisan enrolments:restore-flags-from-offerings
# or:
php artisan enrolments:restore-flags-from-offerings --from-snapshot=storage/app/enrolments/backfill-YYYYMMDDHHMMSS.json

# 3) Only if you also need to drop the new tables (old code does not read them)
php artisan migrate --force --path=database/migrations/2026_08_23_210000_create_application_offering_tables.php --rollback

# 4) Deploy previous application release (main)
# 5) Clear caches; php artisan up
```

If `211000` failed mid-DDL, or trees are empty and offerings rows are empty: **restore the pre-migrate DB backup** and roll the app to `main`. Do not invent catalogue rows on production.

Rolling code back while leaving columns dropped **will break applications**.

### 7.4 Requirements — Phase A (legacy tables still exist)

```bash
php artisan enrolments:restore-requirements --dry-run
php artisan enrolments:restore-requirements
# deploy previous app release
```

Then optionally:

```bash
php artisan migrate --force --path=database/migrations/2026_08_24_100000_create_application_requirement_tables.php --rollback
```



### 7.5 Requirements — Phase B (legacy tables dropped)

```bash
php artisan migrate --force --path=database/migrations/2026_08_24_100100_drop_legacy_requirement_tables.php --rollback
```

That recreates legacy tables and restores from live `application_*` rows, else the newest non-empty `requirements-backfill-*.json`. If the snapshot is missing: **restore the DB backup taken before Phase B**. Then deploy previous app release.

### 7.6 Full abort

1. Maintenance
2. Restore DB from the Window 0 backup
3. Deploy `main` (`50e43e81`)
4. Cache reset; queues; `up`
5. Re-verify old programme trees

Keep that backup at least 7 days after soak.

---



## 8. Permissions created / changed (not all migrated)


| Ability                               | How it lands                                                                            | Who gets it                                                            |
| ------------------------------------- | --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| `manage:online-application-catalogue` | Migrations `210100` + `220000`, plus Permissions seeder rename from `manage:enrolments` | **Super User only** after `220000`                                     |
| `confirm:class-lists`                 | Config + seeders only                                                                   | VP Academics pack (and Principal via that pack). **Must reseed roles** |
| `view-examinations:dashboards`        | Config + seeders only                                                                   | VP Academics, VP Admin, Principal. **Must reseed roles**               |


Users must log out/in after ability changes.

---



## 9. URLs / APIs


| What                          | Path                                                                                              |
| ----------------------------- | ------------------------------------------------------------------------------------------------- |
| Enrolment setup               | `/institution/enrolments`                                                                         |
| Department catalogue          | `/institution/enrolments/{id}`                                                                    |
| Requirements                  | `/institution/enrolments/{id}/requirements`                                                       |
| Class sizes (enrolment setup) | `/institution/enrolments/{id}/class-sizes`                                                        |
| Applicant lookup              | `/enrolments/applicant-lookup`                                                                    |
| Audit trail                   | `/users/audit-trail`                                                                              |
| Application modes API         | `GET /api/v1/enrolments/course-modes/{department_course}/course/{department_level}/level`         |
| Classes modes API (unchanged) | `GET /api/v1/...` `v1.modes-of-study.course-modes`                                                |
| Level requirements API        | `GET /api/v1/institution-departments/levels/{department_level}/requirements`                      |
| Course requirements API       | `GET /api/v1/institution-departments/{department_level}/courses/{department_course}/requirements` |


Mental model after cutover:

```
Department setup  →  what the department runs (levels, courses, course_level_modes)
Enrolment setup   →  what applicants can choose (application_offering_* + application_*_requirements)
Config → Levels   →  institution-wide “qualification is open”
student_semesters →  per-enrolment semester/phase (class lists and progression)
```

---



## 10. Sign-off


| Step                                                    | Owner | Done (UTC) | Notes |
| ------------------------------------------------------- | ----- | ---------- | ----- |
| Staging dry-runs (semesters / offerings / requirements) |       |            |       |
| Staging trees + requirements match baseline             |       |            |       |
| Production DB backup verified                           |       |            |       |
| Window 1 migrate (through `100000`, **not** `100100`)   |       |            |       |
| Snapshots archived                                      |       |            |       |
| RBAC seeders + smoke                                    |       |            |       |
| Soak started                                            |       |            |       |
| Phase B `100100` (optional, after soak)                 |       |            |       |


**Release SHA:** `026849d2` _______________________  
**Deployed by:** _______________________  
**Date (UTC):** _______________________