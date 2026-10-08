<?php
/**
 * PowerForge Admin - Database connection
 *
 * IMPORTANT: This file does NOT create a new database. It opens the
 * SAME SQLite file the player system (powerforge/) already uses, so
 * both apps read and write the exact same users/gear/inventory/
 * energy_logs data. Nothing here ever DROPs or re-seeds a table.
 *
 * If your two project folders sit side-by-side like this:
 *   some-folder/
 *   ├── powerforge/            (existing player system)
 *   └── powerforge-admin/      (this new admin system)
 *
 * ...the default path below just works. If your layout is different,
 * change EXISTING_DB_PATH to point at your real powerforge.sqlite file.
 */

const EXISTING_DB_PATH = __DIR__ . '/../../powerforge/data/powerforge.sqlite';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (!file_exists(EXISTING_DB_PATH)) {
        die(
            "Could not find the existing PowerForge database at:\n" . EXISTING_DB_PATH . "\n\n" .
            "This admin app is meant to connect to an ALREADY RUNNING player system's database.\n" .
            "Open powerforge-admin/config/database.php and fix EXISTING_DB_PATH to point at your\n" .
            "existing powerforge/data/powerforge.sqlite file.\n"
        );
    }

    $pdo = new PDO('sqlite:' . EXISTING_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Safe for two separate PHP processes (player app + admin app) reading
    // and writing the same file at the same time.
    $pdo->exec('PRAGMA journal_mode = WAL');

    ensureAuditTable($pdo);

    return $pdo;
}

/**
 * Adds an admin_audit_log table if it doesn't exist yet. This is 100%
 * additive — it never touches users/gear/inventory/energy_logs. It just
 * gives you a record of which admin changed what, for accountability.
 * Safe to skip/ignore if you don't want it: nothing else depends on it.
 */
function ensureAuditTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            target TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
}
