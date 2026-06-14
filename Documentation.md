# Support Ticket Portal — Technical Documentation

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3 + Inertia.js v3 |
| Styling | Tailwind CSS v4 |
| Auth | Laravel Fortify |
| Queue | Redis |
| Database | MySQL |
| Testing | PHPUnit 12 |

---

## Architecture

The backend follows a **Controller → Service → Repository** layered architecture.

- **Controllers** handle HTTP, authorisation (`Gate::authorize`), and Inertia rendering. They hold no business logic.
- **Services** own business logic: SLA calculation, role-scoped organisation filtering, event dispatch, change logging.
- **Repositories** are the only layer that touches the database directly. All Eloquent queries live here.
- **DTOs** (`TicketFilterDTO`) carry immutable filter state across layers, replacing loose parameter lists.

```
Request → Controller → Service → Repository → Database
                ↓
           Inertia Response (Vue page + props)
```

### Frontend

Each resource has four standard Inertia pages (`Index`, `Create`, `Edit`, `Show`) under `resources/js/pages/{resource}/`. Navigation uses Wayfinder-generated TypeScript functions for type-safe route URLs. shadcn/ui components (Button, Input, Table, etc.) are used throughout.

---

## Role & Permission Model

Five roles are implemented as a PHP enum (`UserRole`):

| Role | Description |
|---|---|
| `admin` | Full access to everything |
| `agent` | Manages tickets; cannot manage users or organisations |
| `organisation_owner` | Full access within their own organisation |
| `client` | Can submit and view their own tickets |

Authorization is enforced via **Laravel Policies** (one per model) and checked with `Gate::authorize()` in controllers. The `isStaff` accessor on `User` provides a shorthand for admin/agent checks.

### Key permission rules

- Only `admin` can create/edit/delete users and organisations.
- `admin` and `agent` can view all tickets; `organisation_owner` and `client` see only their organisation's tickets.
- `organisation_id` is always force-scoped for non-staff users — they cannot submit tickets for other organisations even if they pass the field in the request.
- `organisation_id` is required for staff when creating a ticket; ignored for non-staff (their own org is used).
- Only `admin` and `agent` see the organisation/assigned-agent fields on the ticket create/edit forms.
- Comments marked `is_internal` are hidden from non-staff users.

---

## SLA Rules

SLA deadlines are calculated at ticket creation time based on priority:

| Priority | SLA Deadline |
|---|---|
| Urgent | 1 hour |
| High | 4 hours |
| Medium | 12 hours |
| Low | 2 days |

If priority is changed after creation, the SLA deadline is **recalculated from the current time**.

### Deadline Status

A computed accessor (`deadlineStatus`) on the `Ticket` model evaluates the current state:

| Status | Condition |
|---|---|
| `completed` | Ticket is resolved or closed |
| `overdue` | Past SLA deadline and still open |
| `due-soon` | Within 1 hour of deadline |
| `on-track` | More than 1 hour remaining |

A matching SQL scope (`scopeDeadlineStatus`) mirrors this logic at the database level for filtering.

---

## Events & Queues

- **`TicketCreated` event** — dispatched when a `client` or `organisation_owner` creates a ticket.
- **`SendTicketCreatedNotification` listener** — implements `ShouldQueue`; fetches all agents and queues one `TicketCreatedMail` per agent via Redis.
- **Change logging** — `Log::info` is written whenever a ticket's status or priority changes, recording who changed it and from/to values, with `ticket_id` context.

---

## Key Models

| Model | Notable features |
|---|---|
| `Ticket` | `deadlineStatus`, `slaDeadlineFormatted`, `createdAtFormatted` accessors; `scopeDeadlineStatus`, `scopeGlobalSearch`, `scopeSorting` scopes |
| `User` | `isStaff` accessor; `HasTableFilters` trait |
| `Comment` | `is_internal` flag; hidden from non-staff via query scope |
| `Organisation` | `short_code` (auto-generated unique 7-char code) |

---

## Testing

| Suite | Count | Coverage |
|---|---|---|
| Unit | 13 | SLA deadline calculation, deadline status accessor |
| Feature | 56 | Ticket creation by all roles, field visibility by role, auth, organisation CRUD, settings |

Run with:
```bash
./vendor/bin/sail artisan test --compact
```

---

## Local Development

```bash
# Start all services
./vendor/bin/sail up -d

# Fresh database with seed data
./vendor/bin/sail artisan migrate:fresh --seed

# Run queue worker
./vendor/bin/sail artisan queue:work

# Frontend (dev mode)
./vendor/bin/sail npm run dev
```

Seed creates: 1 admin (`admin@admin.com` / `password`), 3 agents, 20 organisations, ~84 users, 100 tickets, ~800 comments.
