# PROJECT_MASTER_SPECIFICATION.md

## BUP ICT Alumni Association Platform

**Tagline:** Connecting Generations, Building the Future.
**University:** Bangladesh University of Professionals (BUP)
**Department:** Department of ICT

---

## 1. Vision

A permanent, scalable alumni association platform for the Department of ICT, serving as the
official digital home for graduates from Batch 01 onward. The department currently runs up
to Batch 12; the Association currently covers Batch 01–08. The platform must never assume a
fixed batch range — new batches are data, added through the system, not code changes.

## 2. Technology stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Database | MySQL |
| Frontend | Laravel Blade, Bootstrap 5, vanilla JavaScript |
| Dev environment | XAMPP, VS Code |
| Package management | Composer, npm |
| Version control | Git |

No frontend framework (React/Vue/Angular/Livewire/Inertia) is introduced unless a future
phase explicitly requires it.

## 3. Core architectural rules

These rules are binding across all future phases:

1. **Batches are never hardcoded.** The `batches` table is the single source of truth for
   which batches exist. Adding Batch 09+ must never require a code change or redeployment.
2. **Users and alumni are separate concepts.** `users` represents an authentication account.
   `alumni` represents an official, department-verified record. An alumni record can exist
   with no linked user account (`alumni.user_id = null`, `status = unclaimed`). A user
   account is created only when someone registers and is matched or verified.
3. **No feature is implemented ahead of its phase**, even if its database foundation exists.
   A table existing (e.g. `otp_verifications`) does not imply the corresponding workflow is
   built.
4. **Roles and permissions are schema-only in Phase 1.** `roles`, `permissions`,
   `role_user`, and `permission_role` exist and the four roles are seeded, but no controller
   enforces them yet.

## 4. Phase 1 scope (approved, implemented)

- Database foundation: `roles`, `permissions`, `role_user`, `permission_role`, `batches`,
  `alumni`, `verification_requests`, `otp_verifications`
- Eloquent models with relationships (`Batch hasMany Alumni`, `Alumni belongsTo Batch`,
  `Alumni belongsTo User` (nullable), `User hasOne Alumni`, `User belongsToMany Role`,
  `Role belongsToMany Permission`)
- Factories and seeders (four roles; Batch 01–08 as structural data only — no personal
  alumni data seeded)
- Public site foundation: Home, About, Batches, Alumni Directory (placeholder), Events
  (placeholder), Contact
- Auth page shells: Login, Register, Forgot Password (non-functional — UI only)
- Authenticated placeholders: Alumni Dashboard, Admin Dashboard (guarded by `auth`
  middleware only, not yet role-restricted)
- Feature tests for the above
- `README.md` and this specification

## 5. Explicitly out of scope for Phase 1

The following are architecturally anticipated (schema and structure allow for them) but
**not implemented**:

- Online elections (President, Vice President, General Secretary, Joint Secretary,
  Treasurer, Organizing Secretary, Executive Members, Logistics, Assistant General
  Secretary, Assistant Joint Secretary, and other positions); yearly election cycles;
  physical election result management
- Email OTP registration flow and password reset flow (tables exist; logic does not)
- Alumni verification review workflow/UI (table exists; review UI does not)
- Gmail/email notifications and in-app website notifications
- Google Form / Excel importer; automatic alumni photo import and matching
- Job portal, mentorship system, higher-studies tracking, entrepreneur status
- Achievement wall, events module, gallery/batch gallery, alumni stories
- Digital alumni ID
- Advanced search, alumni directory filtering/pagination
- Batch announcements, association-wide announcements
- Reports, statistics dashboards beyond static placeholders
- Multiple-administrator management, admin handover tooling
- Full role-based authorization enforcement (policies/gates wired to seeded roles)

## 6. Roles (seeded, not yet enforced)

1. Super Admin
2. Admin
3. Batch Representative
4. Alumni

## 7. Future registration flow (architecture prepared, not built)

**Case A — matching alumni record exists:**
Student ID + Email → match `alumni` record → send OTP → verify OTP → set password →
create `users` record → link `alumni.user_id`

**Case B — no matching record:**
Student ID + Email → no match → create `verification_requests` row → Admin/Batch
Representative review → if approved → OTP → password → `users` record created

## 8. Data ownership notes

- Real alumni data will arrive later via Google Forms/Excel import (a future phase) — none
  is seeded or fabricated in Phase 1.
- Batch 01–08 seeded as structural records (name/code only) because they are real,
  department-defined batches, not personal data about individuals.

## 10. Phase 2 — Registration, OTP, Login, Profile (implemented)

Corrects and supersedes any earlier assumption that account email/phone must match the
master record.

- **Student ID is the sole lookup key** for the official record. It is stored as
  `VARCHAR`, never assumed to follow a fixed format, and batch is never derived from it —
  it is always read from the matched `alumni.batch_id`.
- **Master data vs. account data** are structurally separate: `alumni.email` /
  `alumni.phone` are reference data from the official record; `users.email` / `users.phone`
  (new in Phase 2) are the actual login credentials, submitted fresh at registration and
  allowed to match or differ from the master values — nothing in the code compares them.
- **Registration flow**: Student ID lookup → if found and unclaimed, show name/batch → collect
  new email/phone/password (held in session only) → OTP sent to the new email → on
  verification, `users` row is created and `alumni.user_id` is linked, `alumni.status`
  becomes `active`. If no record is found (or it's already linked), the person is routed to
  a `verification_requests` submission instead — no account is silently created.
- **Editable vs. official fields**: `student_id`, `full_name`, and `batch_id` are never
  editable by the alumni (enforced server-side by the profile Form Request's field
  whitelist, not merely by hiding inputs). `current_institution`, `designation`, `company`,
  `higher_studies`, `address`, `linkedin_url`, `facebook_url`, `blood_group` (all on
  `alumni`) plus account `email`/`phone` (on `users`) are editable via the dashboard profile
  page.
- **Known overlap in the source spec**: "Current Job / Institution" was described as master
  data while "Company" was described as account data. Both are implemented as distinct,
  independently editable fields rather than merged — flag if that's not what was intended.
- **Not implemented in Phase 2**: SMS OTP, the Excel/Google Form importer itself (this phase
  assumes matching `alumni` rows already exist), admin review UI for verification requests,
  forgot-password (still a placeholder), rate-limit tuning beyond basic `throttle` middleware.


## 11. Review checkpoint

Phase 1 and Phase 2 (as corrected above) are foundations for review. No feature listed in
Section 5 as out-of-scope should begin until explicitly approved.
