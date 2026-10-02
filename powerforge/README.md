# PowerForge

A gear marketplace, loadout customizer, and bio-energy tracker built for **Scenario 3**:
*"Superpowers require massive amounts of biological energy, sparking a new industry of suit tech, power dampeners, and specialized gadgets."*

Heroes, villains, and civilians register, browse suit tech / dampeners / gadgets in the marketplace, buy gear with credits, build an equipped loadout capped by their bio-energy budget, sell gear back for credits, and log energy use over time.

## Tech stack
- **Backend:** PHP 8 (plain PHP, PDO, no framework)
- **Database:** SQLite (zero-config — a single file, auto-created on first run)
- **Frontend:** HTML/CSS + vanilla JS (fetch/AJAX for equip/unequip)

## Becoming an admin

There's no "Admin" option in the signup form on purpose — admin rights are never something a visitor should be able to grant themselves through the UI. Instead:

1. Register a normal account first (any role — hero/villain/civilian, it doesn't matter, it'll be overwritten).
2. In the VS Code terminal, from the `powerforge` folder, run:

   ```bash
   php tools/promote_admin.php your_username
   ```

3. Log out and back in. An **Admin** link now appears in the nav bar.

The Admin panel (`admin.php`) lets you:
- **Add/delete gear** in the marketplace catalog
- **Edit any user** — change their role (including promoting/demoting other admins), adjust their credits or max bio-energy
- **Delete user accounts**

`admin.php` checks `role === 'admin'` on every request via `requireAdmin()` in `includes/auth.php`, so non-admins are redirected away even if they guess the URL.

## Running in VS Code

1. Install the **PHP** extension in VS Code (optional but recommended for syntax help) — you just need PHP itself installed on your machine (PHP 8.0+ with the `pdo_sqlite` extension, which ships enabled by default).
2. Open this `powerforge` folder in VS Code.
3. Open the built-in terminal (`` Ctrl+` ``) and run:

   ```bash
   php -S localhost:8000
   ```

4. Visit **http://localhost:8000** in your browser.
5. Click **Register** to create a hero, villain, or civilian account. You'll start with:
   - 500 credits
   - 100 max bio-energy (fully charged)

No database setup, no `.env`, no `composer install` — the SQLite file is created automatically at `data/powerforge.sqlite` the first time the app runs.

> To reset all data, just delete `data/powerforge.sqlite` and reload the app — it'll reseed the gear catalog.

## Project structure

```
powerforge/
├── index.php            # Dashboard (energy, credits, loadout, recent activity)
├── marketplace.php      # Browse & buy suits / dampeners / gadgets
├── customizer.php       # Equip/unequip owned gear (AJAX, energy-budget capped) + sell gear
├── tracker.php          # Log bio-energy gains/uses, view history
├── admin.php            # Admin-only: manage gear catalog & users
├── login.php / register.php / logout.php
├── tools/
│   └── promote_admin.php # CLI script — run once to make your first admin
├── api/
│   └── toggle_equip.php # AJAX endpoint used by the customizer
├── config/
│   └── database.php     # PDO/SQLite connection + auto-seed schema
├── includes/
│   ├── auth.php          # Session helpers, adjustEnergy(), h(), flash()
│   ├── header.php / footer.php
├── css/style.css
├── js/app.js
└── data/                 # SQLite DB file lives here (auto-created)
```

## Core mechanics

- **Marketplace:** 10 seeded gear items across 3 categories (suit, dampener, gadget), each with a price, energy drain, and power rating.
- **Customizer:** Equipping gear is blocked client+server-side if total drain would exceed your `max_energy` — mirroring the "biological energy budget" concept from the scenario. Owned gear can also be **sold back** here for 50% of its original price (instant credit refund, item removed from inventory).
- **Roles:** Hero, Villain, or Civilian — all three can buy, equip, and sell gear identically; the role is currently a flavor/identity distinction shown on the dashboard.
- **Energy Tracker:** Freeform log of energy gained (resting, recharging) or spent (training, combat, gear use), each entry timestamped and reflected in your current reserve.

## Switching to MySQL (optional)

If you'd rather use MySQL/MariaDB instead of SQLite, only `config/database.php` needs to change — swap the `PDO('sqlite:...')` line for a `PDO('mysql:host=localhost;dbname=powerforge;charset=utf8mb4', $user, $pass)` connection and adjust `AUTOINCREMENT` → `AUTO_INCREMENT` / `CURRENT_TIMESTAMP` defaults in the schema. Every query elsewhere uses standard PDO and works unchanged.
