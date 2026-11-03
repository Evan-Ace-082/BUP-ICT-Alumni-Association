# BUP ICT Alumni Association Platform

**Connecting Generations, Building the Future.**

Official alumni association platform for the Department of ICT, Bangladesh University of
Professionals (BUP).

> **Status: Phase 1 + Phase 2 (registration/OTP/login/profile) implemented.** Phase 2's
> Excel/Google Form importer, admin review UI, and SMS OTP are still not implemented. See
> `PROJECT_MASTER_SPECIFICATION.md` for full scope and phase boundaries.

## Technology stack

- PHP 8.2, Laravel 12
- MySQL
- Blade, Bootstrap 5, vanilla JavaScript
- XAMPP (local development), Composer, npm, Git

## What's included in Phase 1

- Database foundation: `roles`, `permissions`, `role_user`, `permission_role`, `batches`,
  `alumni`, `verification_requests`, `otp_verifications`
- Eloquent models and relationships for all of the above
- Factories and seeders (roles + Batch 01–08, no fake alumni data)
- Public site foundation (Home, About, Batches, Alumni Directory placeholder, Events
  placeholder, Contact) with a reusable Blade layout
- Auth page placeholders (Login, Register, Forgot Password) — **not functional yet**
- Basic authenticated dashboard and admin placeholders
- Feature tests covering the above

See `PROJECT_MASTER_SPECIFICATION.md` for what is explicitly **out of scope** for this phase.

## Requirements

- PHP >= 8.2 with the extensions Laravel 12 requires
- Composer
- MySQL (via XAMPP or standalone)
- Node.js + npm

## Installation (files added on top of your existing project)

These files are written to be merged into your existing local project at
`C:\xampp\htdocs\bup-ict-alumni`. No new Laravel project, `composer.json`, or `vendor/`
directory is included — this assumes Laravel is already installed and booting.

1. Copy the files from this delivery into your project, preserving their relative paths
   (`app/`, `database/`, `routes/`, `resources/`, `tests/`, `public/css/app.css`).
2. Review `app/Models/User.php` and `database/seeders/DatabaseSeeder.php` — these **replace**
   your existing files, so diff them against your current versions if you've already made
   changes there.
3. Install/refresh dependencies (no new packages were added in Phase 1, this is just a
   safety step):

   ```bash
   composer install
   ```

## Environment configuration

Your `.env` should already be configured from your existing setup. Nothing in Phase 1
requires new environment variables. Double-check these are set for local XAMPP development:

```
APP_NAME="BUP ICT Alumni Association"
APP_URL=http://localhost/bup-ict-alumni/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bup_ict_alumni
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
```

With `MAIL_MAILER=log`, registration OTP codes are written to `storage/logs/laravel.log`
instead of being sent over SMTP — open that file after registering to find the code. Switch
to a real mailer once SMTP credentials are available.

## Database setup

Run the new Phase 1 migrations (safe to run alongside your existing, already-migrated
default Laravel tables — nothing here touches or drops them):

```bash
php artisan migrate
```

This creates: `roles`, `permissions`, `role_user`, `permission_role`, `batches`, `alumni`,
`verification_requests`, `otp_verifications`.

## Seeding

```bash
php artisan db:seed
```

This seeds:
- The four roles: Super Admin, Admin, Batch Representative, Alumni
- Batch 01 through Batch 08 (structural records only — no personal alumni data)
- One development test user (`admin@example.test`), skipped automatically in production

## Running the application

Via XAMPP: visit `http://localhost/bup-ict-alumni/public/` (or your configured virtual
host).

Via Artisan's built-in server:

```bash
php artisan serve
```

If you're editing CSS/JS in `resources/` in later phases and wiring them through Vite, run:

```bash
npm install
npm run dev
```

Phase 1's stylesheet (`public/css/app.css`) is served directly and does not require a build
step.

## Running tests

```bash
php artisan test
```

Covers: application/page loads, batch creation and uniqueness, alumni creation and
batch/user relationships, role seeding and assignment, verification request creation, and
OTP record creation/expiry.

## Git workflow

Standard feature-branch workflow recommended for the two maintainers (Nabil and Osman):

```bash
git checkout -b feature/phase-1-foundation
git add .
git commit -m "feat: add Phase 1 alumni/batch foundation (models, migrations, seeders, UI shell)"
git push origin feature/phase-1-foundation
```

Do not commit `.env`, `vendor/`, or `node_modules/` — these are already covered by
Laravel's default `.gitignore`.

## Project structure overview (Phase 1 additions)

```
app/
  Http/Controllers/
    PageController.php              Public pages (home, about, contact, ...)
    Dashboard/DashboardController.php
    Admin/AdminDashboardController.php
  Models/
    User.php (modified)             + roles(), alumni() relationships
    Role.php, Permission.php
    Batch.php, Alumni.php
    VerificationRequest.php, OtpVerification.php
database/
  migrations/                       8 new Phase 1 tables
  factories/                        Batch, Alumni, VerificationRequest, OtpVerification
  seeders/
    RoleSeeder.php, BatchSeeder.php
    DatabaseSeeder.php (modified)
routes/
  web.php (modified)                Public routes + requires below
  auth.php, dashboard.php, admin.php
resources/views/
  layouts/                          app, auth, dashboard
  partials/                         navbar, footer
  home.blade.php, about.blade.php, contact.blade.php
  alumni/, batches/, events/        directory/index placeholders
  auth/                             login, register, forgot-password placeholders
  dashboard/, admin/                authenticated placeholders
public/css/app.css                  Design tokens + component styles
tests/Feature/                      HomePageTest, BatchTest, AlumniTest, RoleTest,
                                     VerificationFoundationTest
```

## Known limitations (Phase 1)

- Login/Register/Forgot Password pages are visual shells only — forms are disabled and do
  not submit.
- No route is actually restricted by role yet; `auth` middleware guards dashboard/admin
  routes, but role-based (Super Admin/Admin) restriction is not implemented.
- No emails are sent; the OTP table exists but nothing writes to or reads from it yet.
- Alumni Directory, Batches, and Events pages are static/placeholder — no filtering, search,
  or pagination.
- No file was verified to run inside an automated build in the delivery environment (see
  assumptions below) — please run `php artisan migrate`, `php artisan db:seed`, and
  `php artisan test` yourself and report back if anything doesn't match.

## Assumptions made

- One-to-one relationship between `users` and `alumni`: `alumni.user_id` is nullable and
  unique, so an alumni record can exist unclaimed, and a user account can only ever link to
  one alumni record.
- `alumni.status` uses `unclaimed | active | inactive`; `verification_requests.status` uses
  `pending | approved | rejected`.
- Batch 01–08 seeded with `batch_code` values `B01`–`B08`; `session` and `graduation_year`
  left null since the real academic session dates weren't provided — fill these in via the
  seeder or (once available) the admin UI.
- No new Composer or npm packages were introduced in Phase 1.
