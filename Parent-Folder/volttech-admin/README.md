# PowerForge Admin

A **separate** application for managing the PowerForge player system — different codebase, different app, **same database**.

## How this connects to your existing system

This app does **not** have its own database. `config/database.php` opens the exact same SQLite file your player system (`powerforge/data/powerforge.sqlite`) already uses. Any change made here — editing a user's credits, adding a gear item, deleting an inventory row — is instantly visible in the player app too, because there's only ever one copy of the data.

```
some-folder/
├── powerforge/               ← your existing player system
│   └── data/powerforge.sqlite   ← the ONE database file
└── powerforge-admin/         ← this new admin system
    └── config/database.php      ← points at ../powerforge/data/powerforge.sqlite
```

If your two folders aren't laid out like that, open `config/database.php` and change `EXISTING_DB_PATH` to the real location of your `powerforge.sqlite` file.

## Setting up your first admin

This app re-uses the player system's login credentials — there's no separate signup form here on purpose (same reasoning as the in-app admin panel: nobody should be able to self-grant admin access).

1. If you don't already have an admin account, register one in the **player app**, then promote it from its terminal:
   ```bash
   cd powerforge
   php tools/promote_admin.php your_username
   ```
2. Open this admin app (`php -S localhost:8001` from inside `powerforge-admin/`, so it doesn't collide with the player app's port) and log in at `http://localhost:8001` with that same username/password.

## What's reused vs. what's new

| | Reused from existing system | New in this app |
|---|---|---|
| **Data** | `users`, `gear`, `inventory`, `energy_logs` — all read/write directly | `admin_audit_log` (optional, additive-only — records who did what) |
| **Auth** | Same `password_hash` column, same `password_verify()` check, same `role` values | Its own session (`powerforge_admin_session` cookie, separate from the player app's session) so logging into one doesn't log you out of the other |
| **Pages** | — | Dashboard, Users, Gear, Inventories, Energy Logs — all brand new, admin-only |

No existing table was altered, dropped, or duplicated. The only schema change is the new `admin_audit_log` table, which nothing in the player app depends on — you can ignore it entirely, or drop it later, without breaking anything.

## Running it

```bash
cd powerforge-admin
php -S localhost:8001
```

Visit `http://localhost:8001`. Keep your player app running separately on its own port (e.g. `localhost:8000`) — they're two independent processes sharing one file.

## Pages

- **Dashboard** (`index.php`) — user counts by role, gear/inventory/credit/energy-log totals, most-owned gear, recently registered users
- **Users** (`users.php`) — search/filter by username or role; inline-edit role, credits, max energy; delete accounts
- **Gear** (`gear.php`) — search/filter by name or category; add, edit, delete catalog items
- **Inventories** (`inventories.php`) — search/filter every player's owned gear across all users; remove items
- **Energy Logs** (`energy_logs.php`) — search by username; manually adjust a user's energy (logged with an `[Admin]` prefix so it's distinguishable from player-driven entries)
- **Audit Log** (`audit_log.php`) — searchable/filterable view of every action recorded in `admin_audit_log` (who did what, and when); the dashboard also shows the 5 most recent entries

## Security notes

- All queries use **prepared statements** (PDO), so user input never gets concatenated into SQL — this prevents SQL injection the same way the player app does.
- Every page calls `requireAdminLogin()` first, which re-checks `role = 'admin'` on *every request* (not just at login) — if an admin's role is changed mid-session, they lose access on their very next click, not just after logging out and back in.
- Passwords are never handled here beyond `password_verify()` against the existing hash — this app never stores or re-hashes a password itself.
- `admin_audit_log` gives you a trail of admin actions (who changed what, when) for accountability.
