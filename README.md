# Integrated Attendance System — SMA 4 Karawang

An attendance management application for SMA 4 Karawang. The system manages
teacher and student attendance, validation workflows, academic years, classes,
student records, teacher-class assignments, reports, and automatic end-of-day
attendance processing.

## Project overview

This application is a standalone Laravel project. It is not an overlay or a
set of files that must be copied into a newly generated Laravel installation.

### Main capabilities

- Role-based access for administrators, student-affairs staff, and teachers.
- Teacher self-attendance with attendance status, notes, supporting documents,
  validation, rejection, and correction workflows.
- Class attendance entry for teachers, including per-student attendance.
- Attendance validation and manual entry for student-affairs staff and
  administrators.
- Master data management for users, students, classes, academic years, and
  teacher-class/subject relationships.
- Dashboard summaries and individual attendance monitoring.
- Attendance reports exportable as PDF and CSV.
- Automatic marking of teacher absences and classes that were not reported at
  the end of an effective school day.

## Technology stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| Database | SQLite by default; MySQL is also supported by Laravel configuration |
| Templates | Blade |
| Frontend build | Vite, Tailwind CSS |
| Browser libraries | Alpine.js and Chart.js via CDN |
| PDF generation | `barryvdh/laravel-dompdf` |
| Testing | PHPUnit through Laravel's test runner |

## Requirements

Install the following before setting up the project:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- A PHP database driver for the selected database:
  - `pdo_sqlite` for the default SQLite setup, or
  - `pdo_mysql` for MySQL
- A writable `storage/` directory and `bootstrap/cache/` directory

On Windows, PHP, Composer, and Node.js must be available in PowerShell or
Command Prompt. Confirm the installations with:

```bash
php --version
composer --version
node --version
npm --version
```

## Quick start

From the project root:

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

Open <http://127.0.0.1:8000> in a browser.

On macOS or Linux, use this equivalent command to copy the environment file:

```bash
cp .env.example .env
```

The seeded data is intended for development and demonstrations. It creates an
active academic year, six classes, sample students, teacher-class assignments,
and recent attendance records.

### Using the Composer setup script

The repository also provides a convenience script:

```bash
composer run setup
```

This installs PHP dependencies, creates `.env` when it is missing, generates an
application key, migrates the database, installs JavaScript dependencies, and
builds frontend assets. It does **not** seed sample data. Run the following
afterward when development data is needed:

```bash
php artisan db:seed
```

## Environment configuration

The committed [`\.env.example`](./.env.example) is configured for SQLite and is
the easiest way to run the application locally:

```dotenv
DB_CONNECTION=sqlite
```

The repository includes `database/database.sqlite`. If you create a new clone
without that file, create an empty SQLite database before migrating:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

For MySQL, update the database section in `.env` and create the database
yourself before running migrations:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_sman4
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Useful application settings are in [`config/absensi.php`](./config/absensi.php):

- `nama_sekolah` and `alamat_sekolah`: school identity shown in reports.
- `timezone`: application timezone, currently `Asia/Jakarta`.
- `libur_mingguan`: ISO weekdays treated as weekly holidays, currently Saturday
  and Sunday (`[6, 7]`).
- `jam_alpa_otomatis`: daily time for the scheduled end-of-day process.
- `bukti_maks_kb`: maximum supporting-document upload size.

## Development accounts

`php artisan migrate --seed` creates the following development accounts. Every
seeded account uses the password `password`.

| Role | Username(s) |
| --- | --- |
| Administrator | `admin` |
| Student affairs | `kesiswaan1`, `kesiswaan2` |
| Teacher | `guru1` through `guru7` |

Do not use these credentials in production. Do not run the development seeder
against a production database.

## Role responsibilities

### Administrator

- Manage application users.
- Access dashboards, master data, validation, and reports.
- Review and process attendance records.

### Student affairs

- Manage students, classes, academic years, and teacher-class relationships.
- Validate, reject, correct, or manually enter teacher and student attendance.
- View dashboards and export attendance reports.

### Teacher

- Submit personal attendance.
- Submit attendance for assigned classes and students.
- Correct attendance that has been rejected.
- View applicable attendance history and export reports.

## Typical workflows

1. Sign in with an account for the required role.
2. Configure the active academic year, classes, students, and teacher
   relationships.
3. Teachers submit personal attendance and class attendance.
4. Student affairs or an administrator validates, rejects, or corrects
   submissions.
5. Users review dashboard summaries and generate PDF or CSV reports.
6. The end-of-day command creates automatic absences for eligible teachers and
   marks classes that were not reported.

## Scheduler

The scheduled command is defined in [`routes/console.php`](./routes/console.php)
and runs at 23:59 in the `Asia/Jakarta` timezone:

```bash
php artisan absensi:tutup-hari
```

The command is idempotent and accepts an optional date for testing:

```bash
php artisan absensi:tutup-hari --tanggal=2026-10-07
```

In production, run Laravel's scheduler every minute. Linux cron example:

```cron
* * * * * cd /path/to/absensi-sman4 && php artisan schedule:run >> /dev/null 2>&1
```

On Windows, configure Task Scheduler to run the equivalent command once per
minute:

```text
php C:\path\to\absensi-sman4\artisan schedule:run
```

The end-of-day command skips configured weekly holidays. Review
`config/absensi.php` before deploying.

## Frontend development

Build production assets after changing CSS or JavaScript:

```bash
npm run build
```

For live frontend updates during development, run Vite in a separate terminal:

```bash
npm run dev
```

The application also loads Alpine.js, Chart.js, and the configured font through
CDN URLs. If the application must run on a network without internet access,
vendor those browser assets locally and update the Blade layouts accordingly.

## Testing and quality checks

Run the application test suite with:

```bash
php artisan test
```

The project uses PHPUnit. Useful Laravel checks while developing are:

```bash
php artisan route:list
php artisan config:clear
php artisan migrate:status
```

For a clean local database populated with fresh development data, use:

```bash
php artisan migrate:fresh --seed
```

This command destroys all data in the configured database. Never run it against
a shared or production database.

## File and data locations

- `app/`: models, controllers, policies, services, requests, middleware, and
  domain support classes.
- `config/absensi.php`: attendance-specific configuration.
- `database/migrations/`: database schema.
- `database/seeders/DatabaseSeeder.php`: development sample data.
- `resources/views/`: Blade pages and components.
- `routes/web.php`: authenticated web routes and role boundaries.
- `routes/console.php`: scheduled tasks.
- `storage/app/private/bukti/`: private teacher supporting documents.
- `public/`: public assets.

Supporting documents are private and are served through an authorized route;
they should not be moved into `public/`.

## Troubleshooting

### `Vite manifest not found`

Install JavaScript dependencies and build the assets:

```bash
npm install
npm run build
```

### Database or session errors

Verify that the selected PHP database extension is enabled and that the
database exists. Then clear cached configuration:

```bash
php artisan config:clear
php artisan migrate
```

### Permission errors

Ensure the process running PHP can write to `storage/` and
`bootstrap/cache/`. Also verify that uploaded supporting documents can be
written under `storage/app/private/bukti/`.

### Stale application configuration

After changing `.env` or configuration files, clear cached configuration:

```bash
php artisan optimize:clear
```

## Production notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a real `APP_URL`.
- Generate a unique `APP_KEY`; never share or commit `.env`.
- Use a managed database and back it up before migrations.
- Replace all seeded credentials with strong, unique passwords.
- Run `php artisan migrate --force` during deployment, not the development
  seeder.
- Configure the scheduler and a process manager for queue workers if queued
  work is enabled.
- Review storage permissions and protect private uploads.
- Build and deploy the frontend assets with `npm run build`.

## License

No separate project license is currently defined in this repository. Confirm
the intended license with the project owner before redistributing the
application.
