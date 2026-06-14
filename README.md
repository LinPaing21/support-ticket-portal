# Support Ticket Portal

A multi-tenant support ticket management system built with Laravel 13, Vue 3, and Inertia.js.

---

## Frontend Approach

I initially considered an API-first approach with a standalone Vue SPA + Laravel API backend, but decided against it — that separation adds meaningful complexity (auth tokens, CORS, separate deploys) that would eat into the timebox without adding user-facing value.

Instead I chose **Vue 3 + Inertia.js + shadcn/vue**, using Laravel's official Vue starter kit as the base. This accelerated development significantly: routing, auth, and the app shell were ready from the start, letting me focus on the domain logic. I read the Inertia v3 docs, resolved uncertainties with AI assistance, and was confident the full scope could be delivered within ~8 hours.

---

## Architecture & Design Choices

The backend follows a **Controller → Service → Repository** layered architecture — a pattern I use regularly in my day-to-day work and am very comfortable with.

- **Controllers** handle HTTP, authorisation via `Gate::authorize()`, and Inertia rendering. No business logic lives here.
- **Services** own all business logic: SLA deadline calculation, role-scoped organisation filtering, event dispatching, and change logging.
- **Repositories** are the only layer that touches the database. All Eloquent queries live here.
- **DTOs** (`TicketFilterDTO`) carry filter state immutably across layers, replacing loose multi-parameter lists as filters grew in complexity.
- **Policies** enforce authorisation per model, one policy class per resource.
- **Events & queued listeners** handle side effects (email notification on ticket creation) without blocking the request.

The frontend follows Inertia's page-component model — one Vue component per CRUD action under `resources/js/pages/{resource}/`. Route URLs are generated via **Wayfinder** (type-safe TypeScript functions), and UI is built on **shadcn/vue** components with Tailwind CSS v4.

**Database schema:** [View ERD on dbdiagram.io](https://dbdiagram.io/d/Support-Ticket-6a2ccb4e5c789b8acb752e92)

For full technical details see [`Documentation.md`](Documentation.md).

---

## SLA Rules & Role/Permission Model

### SLA Rules

I originally planned to calculate SLA deadlines based on a combination of organisation tier, environment, and priority — but scoped that down to **priority only** to stay within the timebox.

| Priority | SLA Deadline |
|---|---|
| Urgent | 1 hour |
| High | 4 hours |
| Medium | 12 hours |
| Low | 2 days |

The deadline is set at ticket creation and **recalculated from the current time** if priority is changed afterwards. A `deadlineStatus` accessor evaluates the live state (`on-track`, `due-soon`, `overdue`, `completed`) and a matching SQL scope mirrors this logic for database-level filtering.

### Role & Permission Model

Five roles are implemented as a PHP enum. Authorization is enforced via Laravel Policies checked with `Gate::authorize()` in controllers.

| Role | Access |
|---|---|
| `admin` | Full access to all resources |
| `agent` | Manages tickets; cannot manage users or organisations |
| `organisation_owner` | Full access scoped to their own organisation |
| `client` | Can submit and view their own organisation's tickets |

Key rules:
- Non-staff users (`organisation_owner`, `client`) are always force-scoped to their own organisation — they cannot submit or view tickets from other organisations even if they manipulate the request payload.
- `organisation_id` is required for staff when creating a ticket; silently ignored for non-staff (their own org is used instead).
- Internal comments (`is_internal`) are hidden from non-staff users at the query level.
- Organisation and assigned-agent fields are only visible on ticket forms for admin and agent roles.

I would have preferred to use **Laravel Spatie Roles & Permissions** for a more extensible RBAC system that admins could manage via a portal — but policy-based auth was the right call to complete within the timebox.

---

## ⏱️ Time Spent & Scoping

This prototype was scoped and built within a strict timebox, taking a total of **7 hours and 35 minutes (excluding documentation)** of effective work. The time was allocated across the software development lifecycle as follows:

- **Phase 1: Architecture & Setup (1 Hour, 30 Minutes)**
  - Database schema modeling, ERD planning, framework bootstrapping (Laravel + Inertia + Vue 3), and authentication scaffolding.
- **Phase 2: Core Domain Logic & CRUD (3 Hours, 30 Minutes)**
  - Building Organisation, User, and Ticket database migrations, models, factories, form validation requests, and core controller actions.
- **Phase 3: Business Features & Enhancements (2 Hours)**
  - Implementing the comment/internal note authorization controls, advanced queueable email notifications, search/filtering logic, and system loggers.
- **Phase 4: Testing & Documentation (35 Minutes)**
  - Writing representative HTTP feature tests for role-based data isolation, validating SLA state shifts, and compiling technical documentation.

All core requirements were completed within the timebox. Beyond the base requirements, I also implemented:

- Advanced filtering on the ticket index (status, priority, deadline status, organisation)
- Email notifications to agents on ticket creation (queued via Redis)
- `Log::info` on status and priority changes
- Pagination on tickets, users, organisations, and comments
- GitHub-style comment section with edit/delete and internal note toggle for staff

---

## Next Steps

### What I would do next with more time

- **Scheduled notifications** — send reminder emails to assigned agents when tickets remain unresolved/unclosed past a threshold; auto-close in-progress tickets on inactive threads via Laravel Scheduler.
- **Client self-registration with short code** — allow clients to register and automatically connect to their organisation using its `short_code`.
- **Rich text editor** — integrate Summernote or a Markdown editor for ticket and comment bodies.
- **File attachments** — add an attachment input that stores files to object storage (S3/R2).
- **Dashboard metrics** — show unresolved/unclosed ticket counts per organisation and per agent.
- **Broader test coverage** — feature tests for ticket editing, comment CRUD, and ticket workflows across all roles and edge cases.

### Known limitations & shortcuts

- **AI-assisted development** — I created a `CRUD_GUIDES.md` and worked with AI in a pair-programming style, supervising and directing the output rather than writing every line manually.
- **Laravel starter kit + shadcn** — used as scaffolding to avoid boilerplate and accelerate UI development.
- **SLA recalculation on priority change** — recalculates from `now()` rather than from the original creation time, which may not match all business expectations. This is a known trade-off worth discussing with stakeholders.
- **Ticket field edit permissions** — which fields each role can modify is not fully granular; this would benefit from a requirements conversation before implementing.
- **Role accessors** — I'd add `isAdmin`, `isAgent`, `isClient`, etc. as dedicated accessors on the `User` model (similar to the existing `isStaff`) to eliminate duplicate inline role checks scattered across the codebase.
- **Event registration** — I missed that Laravel 11+ uses automatic event discovery, which caused double-firing. Coming from Laravel 8 projects where manual `EventServiceProvider` registration is the norm, this was a quick lesson learned.

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

### Running tests

```bash
./vendor/bin/sail artisan test --compact
```

