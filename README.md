
# College Management System

College Management System – Project Description
The College Management System is an integrated software solution developed to manage various activities and functions within a college. This system digitizes and automates tasks such as student admissions, class scheduling, attendance tracking, examination management, result processing, faculty management, and fee collection.

### Objectives

- Improve efficiency in handling college operations.
- Minimize paperwork and manual errors.
- Provide a centralized database for storing academic and administrative records.
- Offer role-based access to different users (admin, teachers, students).

### Core Modules

- Student Management
- Registration & admission
- Personal & academic profiles
- Attendance tracking
- Department Management
- Course & Subject Management
- Examination & Results
- Grade entry & results generation
- Payment tracking
- Receipts and pending dues
- Library Management (Optional)
- Notifications & Reports
- SMS/email alerts
- Custom reports for analysis

### Users

- Admin: Full control over the system.
- Department: Manage classes, attendance, and grades.
- Students: View schedules, attendance, exam results, and fees.

### Benefits

- Efficient management of college resources.
- Better communication among stakeholders.
- Accurate record-keeping and easy data retrieval.
- Scalable to accommodate more features like hostel.

### Development Stack

- Laravel - [https://laravel.com/](https://laravel.com/)
- InertiaJs - [https://inertiajs.com/](https://inertiajs.com/)
- VueJs - [https://vuejs.org/](https://vuejs.org/)
- Shadcn - [https://ui.shadcn.com/](https://ui.shadcn.com/)
- TailwindCSS - [https://tailwindcss.com/](https://tailwindcss.com/)
- Database: MySQL / PostgreSQL / MongoDB / MsSQL

## Install

- Remember to change the env file to your database details.

```bash
git clone git@github.com:Penstej-Systems/hrepoly.git
cd hrepoly
mkdir -p storage/framework/cache/laravel-excel
chown -R $WEB_USER:$WEB_USER storage bootstrap/cache   # e.g. www-data
chmod -R ug+rwx storage bootstrap/cache
cp .env.example .env
composer install && npm install
php artisan key:gen
php artisan migrate --seed
php artisan storage:link
```

Excel export/import temporary files use `sys_get_temp_dir()` via `config/excel.php`, so they do not depend on `storage/framework/cache/laravel-excel` being writable. The directory above is still required for Laravel cache and other framework storage.

## Queue Process Jobs

```bash
php artisan queue:work --daemon database --env=production --queue=default,bank-statements --delay=300 --tries=10 --timeout=120
php artisan queue:health --queues=default,bank-statements
OR
php artisan horizon
```

### Production storage permissions

If Excel downloads fail with `Permission denied` under `storage/framework/cache/laravel-excel`, fix storage ownership on the server (adjust `www-data` if your PHP-FPM user differs):

```bash
cd /var/www/hrepoly
mkdir -p storage/framework/cache/laravel-excel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
sudo -u www-data touch storage/framework/cache/laravel-excel/.write-test && \
sudo -u www-data rm storage/framework/cache/laravel-excel/.write-test
```

After deploying `config/excel.php`, Excel temp files use `sys_get_temp_dir()` and no longer require the `laravel-excel` cache directory to be writable.

### Production recovery sequence (Supervisor + database queue)

```bash
php artisan optimize:clear
php artisan queue:health --queues=default,bank-statements
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart <program-name>:*
php artisan queue:work --once --queue=default,bank-statements -v
php artisan queue:health --queues=default,bank-statements
php artisan queue:failed
```

### Important

Do not commit the following files to the git repo as they are all dynamically generated
and might overwrite others settings. DO NOT COMMIT PASSWORD, SECRETS, API KEYS etc to repo.

```bash
.idea
.env
node_modules
vendor
public/build
public/vendor
public/mix-manifest.json
.DS_Store
```

### Development Tools

Run and scan your code for bugs even before you write unit tests (Static code
analysis) [https://github.com/nunomaduro/larastan](https://github.com/nunomaduro/larastan)

```php
composer analyse
```

Run a scan on the codebase to check the code quality [https://github.com/nunomaduro/phpinsights](https://github.com/nunomaduro/phpinsights)

```php
composer insights
```

#### Run Queue

```php
php artisan queue:listen
```

#### Database

```php
//Re-run migration with seeding
php artisan migrate --database=mysql --seed"
#### Run a specific database seeder
php artisan db:seed --class=Database\\Seeders\\Schemes\\SchemesTableSeeder

## Testing

Create 'testing' database in your mysql

```php
php artisan test --stop-on-failure
php artisan test --filter=AddressTest #test single file
```

## Notes

## Credits

- James Gudhlanga

---

