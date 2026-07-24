#!/usr/bin/env bash
# Performance regression suite (Section A). Do not list files already under a directory.
set -euo pipefail

cd "$(dirname "$0")/.."

php artisan test \
  tests/Feature/Dashboard \
  tests/Feature/Lecturer/LecturerDashboardTest.php \
  tests/Feature/Auth/HandleInertiaRequestsPermissionsTest.php \
  tests/Feature/HandleInertiaRequestsAppVersionTest.php \
  tests/Feature/Console/PerformanceDiagnoseCommandTest.php \
  tests/Feature/Rbac \
  tests/Feature/Users/UpdateStaffUserTest.php \
  tests/Feature/Performance
