# CRM Lead Management System

A small CRM Lead Management System for an insurance/finance company built with
**Laravel 12** (MySQL in development, in-memory SQLite for tests). Sales
employees can manage leads from creation through conversion or closure, with
role-based access for `ADMIN` and `SALES` users.

## Table of Contents

- [Tech Stack](#tech-stack)
- [Features](#features)
- [Database Design](#database-design)
- [Business Rules](#business-rules)
- [Roles & Permissions](#roles--permissions)
- [Installation](#installation)
- [Seed Data & Login](#seed-data--login)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Postman Collection](#postman-collection)
- [Assumptions & Decisions](#assumptions--decisions)

## Tech Stack

| Concern      | Technology                                  |
| ------------ | ------------------------------------------- |
| Framework    | Laravel 12 (PHP >= 8.2)                     |
| Database     | MySQL (development) / in-memory SQLite (tests) |
| Auth         | Laravel Sanctum (Bearer tokens)             |
| Testing      | PHPUnit (Feature tests)                     |
| Code style   | Laravel Pint                                |

## Features

- User management with roles (`ADMIN`, `SALES`), unique email, and active status.
- Lead lifecycle management with auto-generated `lead_code`.
- Duplicate-lead prevention for active leads.
- Enforced status-transition state machine.
- Converted-lead protection (cannot be edited or deleted).
- Follow-up module with date and status rules.
- Search, filter and pagination on the lead listing.
- Status-count dashboard, scoped per role.

## Database Design

```
users ──1:N── leads ──1:N── lead_followups
users ──N:M── roles            (via user_role)
users ──N:1── options          (status_id → USER_STATUS lookup)
options ─────────────── lookup table (USER_STATUS, LEAD_STATUS, LEAD_SOURCE, FOLLOWUP_STATUS)
```

| Table            | Purpose                                                            |
| ---------------- | ------------------------------------------------------------------ |
| `users`          | System users: `name, email (unique), phone, status_id`             |
| `roles`          | `ADMIN`, `SALES`                                                   |
| `user_role`      | Many-to-many pivot between `users` and `roles`                     |
| `options`        | Maintained lookup values (user/lead statuses, sources, follow-up statuses) |
| `leads`          | `lead_code (unique), customer_name, email, phone, source, assigned_to, status, remarks` |
| `lead_followups` | `lead_id, followup_date, notes, status, created_by`                |

All relations use foreign keys with sensible `ON DELETE` behaviour
(`nullOnDelete` for optional assignees/creators, `cascadeOnDelete` for
children, `restrictOnDelete` for lookups). Indexes exist on frequently
filtered columns (`email`, `status`, `source`, `assigned_to`, `lead_id`,
`status_id`) and `options` has a unique `(category, value)` constraint.

User status is **lookup-driven**: `users.status_id` stores the id of the
matching `options` row in the `USER_STATUS` category (`ACTIVE`, `INACTIVE`).
The API resolves it back to the value code (`ACTIVE`/`INACTIVE`) in responses.

## Business Rules

1. **Assignment** – a lead may only be assigned to an `ACTIVE` user with the
   `SALES` role.
2. **Duplicate prevention** – the same customer email may not have more than
   one *active* lead (`NEW` or `FOLLOW_UP`). Once a lead is `CONVERTED`/`LOST`,
   a new lead for the same email is allowed.
3. **Status transitions** – only the following moves are allowed:
   - `NEW → CONTACTED`
   - `NEW → LOST`
   - `CONTACTED → FOLLOW_UP`
   - `CONTACTED → LOST`
   - `FOLLOW_UP → CONTACTED`
   - `FOLLOW_UP → CONVERTED`
   - `FOLLOW_UP → LOST`
   - `CONVERTED` and `LOST` are terminal.
   - `FOLLOW_UP → CONTACTED` and `CONTACTED → LOST` are extra documented
     transitions added to the minimum required flow.
4. **Converted protection** – `CONVERTED` leads cannot be edited or deleted.
5. **Follow-ups** – created only for non-terminal leads (`NEW`, `CONTACTED`,
   `FOLLOW_UP`); `followup_date` cannot be in the past; status is one of
   `PENDING`, `COMPLETED`, `CANCELLED`.

## Roles & Permissions

| Action             | ADMIN | SALES                 |
| ------------------ | ----- | --------------------- |
| Create Lead        | Yes   | Yes (self-assigned)   |
| View Leads         | All   | Own assigned leads    |
| Update Lead        | All   | Own assigned leads    |
| Delete Lead        | Yes   | No                    |
| (Re)Assign Lead    | Yes   | No                    |
| Create Follow-up   | Yes   | Own leads             |
| Dashboard          | All   | Own leads             |

## Installation

### Prerequisites

- PHP >= 8.2 with the `pdo_mysql` extension (web server) and `pdo_sqlite`
  extension (required to run the test suite).
- MySQL server (Laragon ships with one) with a database created for the app.
- Composer.

### Steps

```bash
# 1. Install dependencies
composer install

# 2. Copy the environment file and generate the key
copy .env.example .env        # Windows
# cp .env.example .env        # Unix/macOS
php artisan key:generate
```

```ini
# 3. Configure the database in .env
APP_URL=http://localhost/CRM_Lead_Managment
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm_management
DB_USERNAME=root
DB_PASSWORD=root
```

Create the database (the default Laragon MySQL credentials are `root`/`root`):

```sql
CREATE DATABASE crm_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
# 4. Run the migrations and seed the database
php artisan migrate --seed

# 5. Serve the app
# Option A - Laragon: copy the project into C:\laragon\www and visit
#     http://localhost/CRM_Lead_Managment/
# Option B - PHP dev server
php artisan serve
```

The API is available at:

- Laragon: `http://localhost/CRM_Lead_Managment/api`
- `php artisan serve`: `http://localhost:8000/api`

> **Laragon note:** if you get `could not find driver (Connection: mysql)`,
> enable `extension=pdo_mysql` in `C:\laragon\bin\php\<version>\php.ini` and
> restart the server. For the test suite you also need `extension=pdo_sqlite`
> enabled in the PHP CLI build (`php --ini` to find the active `php.ini`).

## Seed Data & Login

The seeder creates the roles, the lookup `options` (including `USER_STATUS`
with `ACTIVE` / `INACTIVE`) and four users:

| Email                  | Password  | Role     | Status   |
| ---------------------- | --------- | -------- | -------- |
| `admin@example.com`    | `password`| `ADMIN`  | `ACTIVE` |
| `sales@example.com`    | `password`| `SALES`  | `ACTIVE` |
| `sales2@example.com`   | `password`| `SALES`  | `ACTIVE` |
| `inactive@example.com` | `password`| `SALES`  | `INACTIVE`|

Login:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

(With Laragon the URL is `http://localhost/CRM_Lead_Managment/api/login`.)

The response contains a Bearer token. Send it on every protected request:

```http
Authorization: Bearer <token>
```

## Running Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database. The suite covers:

- Lead creation (happy path, validation, duplicate rejection).
- Assignment rules (inactive user, non-SALES user, self-assignment, reassignment).
- Status-transition enforcement.
- Converted-lead protection (edit/delete).
- Follow-up rules (active lead, past date, status updates).
- Role scoping for listing/dashboard.
- Authentication (login, inactive login, protected routes).

## API Reference

Full endpoint documentation with request/response examples lives in
[`docs/api.md`](docs/api.md). Summary:

| Method | URI                        | Description                  |
| ------ | -------------------------- | ---------------------------- |
| POST   | `/api/login`               | Obtain a Bearer token        |
| POST   | `/api/logout`              | Revoke the current token     |
| GET    | `/api/me`                  | Current authenticated user   |
| GET    | `/api/dashboard`           | Lead counts by status        |
| POST   | `/api/leads`               | Create a lead                |
| GET    | `/api/leads`               | List leads (search/filter/paginate) |
| GET    | `/api/leads/{id}`          | Show a single lead           |
| PUT    | `/api/leads/{id}`          | Update a lead                |
| DELETE | `/api/leads/{id}`          | Delete a lead (admin only)   |
| GET    | `/api/leads/{id}/followups`| List a lead's follow-ups     |
| POST   | `/api/leads/{id}/followups`| Create a follow-up           |
| PUT    | `/api/followups/{id}`      | Update a follow-up           |

## Postman Collection

A ready-to-import collection with all requests, variables and example payloads
is included at
[`docs/CRM_Lead_Managment.postman_collection.json`](docs/CRM_Lead_Managment.postman_collection.json).
Import it into Postman, set the `base_url` collection variable to
`http://localhost/CRM_Lead_Managment/api` (Laragon) or
`http://127.0.0.1:8000/api` (`php artisan serve`), and the `Login` request will
store the returned token in the `token` variable automatically.

## Assumptions & Decisions

- **`options` table as source of truth.** User statuses, lead statuses, lead
  sources and follow-up statuses are maintained in the `options` table and
  seeded from the constant classes (`App\Models\User`, `LeadStatus`,
  `LeadSource`, `FollowupStatus`). `users` stores the id of the matching option
  (`status_id`) with a foreign key back to `options`, and the API resolves it
  back to the value code (`ACTIVE`/`INACTIVE`) in responses. The `leads.status` /
  `leads.source` columns store the value code (e.g. `NEW`, `WEBSITE`) which is
  validated against the constants. This keeps rows readable while still being
  driven by configurable lookup data.
- **Database choice.** MySQL is used for development/local runs (matching the
  assignment's intended stack) while the automated test suite runs against an
  in-memory SQLite database for speed and isolation (`phpunit.xml`).
- **Email uniqueness scope.** Email must be unique per *active* lead only (not
  globally), matching requirement 3.2. Emails are normalized to lowercase.
- **Creation status.** When creating a lead the status defaults to `NEW`; a
  caller may optionally supply any valid status. Duplicate-prevention and
  assignment rules still apply.
- **Sales self-assignment.** A `SALES` user creating a lead is automatically
  assigned as the owner unless they explicitly assign to themselves. They may
  never assign to another user; only `ADMIN` may (re)assign.
- **Extra transitions.** `CONTACTED → LOST` and `FOLLOW_UP → CONTACTED` are
  allowed in addition to the mandatory flow (documented in `LeadStatus`).
- **Delete cascade.** Deleting a lead removes its follow-ups
  (`cascadeOnDelete`).
- **Pagination.** Listing uses Laravel's default paginator metadata
  (`data`, `links`, `meta`). `per_page` query parameter is supported.
- **Auth.** Sanctum personal access tokens. Tokens can be revoked via logout.
