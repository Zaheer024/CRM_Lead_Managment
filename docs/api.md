# API Documentation

All endpoints are under `/api` and return JSON. Except `POST /api/login`, every
endpoint requires a Bearer token.

```http
Authorization: Bearer <token>
```

Base URL: `http://localhost:8000/api`

---

## Authentication

### POST `/api/login`

Authenticate and receive a Sanctum Bearer token.

**Request body**

| Field    | Type   | Required |
| -------- | ------ | -------- |
| `email`  | string | yes      |
| `password`| string | yes      |

**Response `200`**

```json
{
  "message": "Login successful.",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": "+1 555-0100",
    "status": "ACTIVE",
    "roles": ["ADMIN"]
  },
  "token": "1|abcdef...",
  "token_type": "Bearer"
}
```

**Errors:** `401` invalid credentials, `403` inactive account, `422` validation.

### POST `/api/logout`

Revokes the current token. **Response `200`** `{ "message": "Logged out successfully." }`

### GET `/api/me`

Returns the authenticated user and roles. **Response `200`**

---

## Dashboard

### GET `/api/dashboard`

Lead counts by status. `ADMIN` sees all leads; `SALES` sees only their own.

**Response `200`**

```json
{
  "total_leads": 100,
  "new": 20,
  "contacted": 30,
  "follow_up": 25,
  "converted": 15,
  "lost": 10
}
```

---

## Leads

### POST `/api/leads`

Create a lead. Requires `ADMIN` or `SALES`. A `SALES` user is auto-assigned as
owner; `ADMIN` may assign to any active `SALES` user.

**Request body**

| Field          | Type   | Required | Notes                                        |
| -------------- | ------ | -------- | -------------------------------------------- |
| `customer_name`| string | yes      |                                              |
| `email`        | string | yes      | valid email; no other active lead with it    |
| `phone`        | string | no       | `+?` digits/spaces/dashes/parens, 7-20 chars |
| `source`       | string | yes      | `WEBSITE`, `REFERRAL`, `PHONE`, `EMAIL`, `CAMPAIGN`, `OTHER` |
| `assigned_to`  | int    | no       | must be an active `SALES` user (admin only beyond self) |
| `status`       | string | no       | one of `NEW`, `CONTACTED`, `FOLLOW_UP`, `CONVERTED`, `LOST` (default `NEW`) |
| `remarks`      | string | no       |                                              |

**Response `201`**

```json
{
  "message": "Lead created successfully.",
  "lead": {
    "id": 1,
    "lead_code": "LD-20260101-AB12",
    "customer_name": "John Doe",
    "email": "john@example.com",
    "phone": "+1 555-123-4567",
    "source": "WEBSITE",
    "assigned_to": 2,
    "assignee": { "id": 2, "name": "Sales User", "email": "sales@example.com" },
    "status": "NEW",
    "remarks": "Interested in term life.",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

**Errors:** `422` business/validation error (duplicate active email, inactive
assignee, non-SALES assignee, bad source/status, sales assigning to others).

### GET `/api/leads`

List leads. `ADMIN` sees all; `SALES` sees only their own.

**Query parameters**

| Parameter     | Type   | Description                                    |
| ------------- | ------ | ---------------------------------------------- |
| `search`      | string | matches `lead_code`, `customer_name`, `email`, `phone` |
| `status`      | string | filter by lead status                          |
| `source`      | string | filter by lead source                          |
| `assigned_to` | int    | filter by assigned employee                    |
| `page`        | int    | page number                                    |
| `per_page`    | int    | items per page (default `15`)                  |

**Response `200`** – paginated result with `data`, `links`, `meta`.

### GET `/api/leads/{id}`

Show a single lead with assignee and follow-ups.

**Errors:** `403` not owned by a `SALES` user, `404` not found.

### PUT `/api/leads/{id}`

Update a lead. Partial updates allowed (any field is optional).

- Status changes are validated against the transition map.
- `CONVERTED` leads cannot be edited at all (`409`).
- Only `ADMIN` may change `assigned_to`; the target must be an active `SALES`
  user.
- Changing `email` to an email that already has an active lead is rejected.

**Response `200`** – `{ "message": "...", "lead": {...} }`

### DELETE `/api/leads/{id}`

Delete a lead. `ADMIN` only. `CONVERTED` leads cannot be deleted (`409`).
Deletes the lead's follow-ups.

**Response `200`** – `{ "message": "Lead deleted successfully." }`

---

## Follow-ups

### GET `/api/leads/{id}/followups`

List follow-ups for a lead (newest first). Scoped by role like leads.

### POST `/api/leads/{id}/followups`

Create a follow-up.

**Request body**

| Field           | Type   | Required | Notes                                     |
| --------------- | ------ | -------- | ----------------------------------------- |
| `followup_date` | date   | yes      | must not be in the past                   |
| `notes`         | string | no       |                                           |
| `status`        | string | no       | `PENDING`, `COMPLETED`, `CANCELLED` (default `PENDING`) |

**Business rules:** lead must not be `CONVERTED` or `LOST` (`422`); the lead
must exist (`404`).

**Response `201`**

```json
{
  "message": "Follow-up created successfully.",
  "followup": {
    "id": 1,
    "lead_id": 1,
    "followup_date": "2026-01-05",
    "notes": "Call back about the quote.",
    "status": "PENDING",
    "created_by": 1,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### PUT `/api/followups/{id}`

Update a follow-up (`followup_date`, `notes`, `status`). The past-date rule
applies when changing the date.

**Response `200`** – `{ "message": "...", "followup": {...} }`

---

## Error Format

Validation and business-rule errors return a consistent JSON shape:

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["The email field is required."] }
}
```

```json
{ "message": "A lead with this email already exists and is still active." }
```

Common status codes: `200` OK, `201` created, `400/422` bad request / business
rule, `401` unauthenticated, `403` forbidden, `404` not found, `409` conflict
(converted lead), `500` server error.
