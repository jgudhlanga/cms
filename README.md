<p align="center"><a href="https://hrepoly.ac.zw" target="_blank"><img src="https://portal.hrepoly.ac.zw/app-assets/images/logo/logo-dark.png" width="400"></a></p>

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
- Laravel - https://laravel.com/
- InertiaJs - https://inertiajs.com/
- VueJs - https://vuejs.org/
- Shadcn - https://ui.shadcn.com/
- TailwindCSS - https://tailwindcss.com/
- Database: MySQL / PostgreSQL / MongoDB / MsSQL

## Install

- Remember to change the env file to your database details.

``` bash
git clone git@github.com:Penstej-Systems/hrepoly.git
chmod -R 777 storage bootstrap/cache
cp .env.example .env && cp .env.ci .env.testing
composer install && npm install
php artisan migrate --seed"
php artisan storage:link
```

## Queue Process Jobs

``` bash
php artisan queue:work --daemon database --env=production --queue=default --delay=300 --tries=10 --timeout=120
OR
php artisan horizon
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
analysis) https://github.com/nunomaduro/larastan

```php
composer analyse
```

Run a scan on the codebase to check the code quality https://github.com/nunomaduro/phpinsights

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
- Penstej Developers
- Peter Mdluli
- Nyasha W. Manhanga
- Shadreck Mahoseni
- Tendai Kumvekera
- James Gudhlanga
****
