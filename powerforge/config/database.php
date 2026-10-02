<?php
/**
 * PowerForge - Database bootstrap
 * Uses SQLite so the app runs with zero external DB setup.
 * File lives at /data/powerforge.sqlite (auto-created on first run).
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbPath = __DIR__ . '/../data/powerforge.sqlite';
    $isNew  = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        seedDatabase($pdo);
    }

    ensureApiTokenColumn($pdo);

    return $pdo;
}

/**
 * Adds users.api_token if it doesn't already exist — lets the new
 * bearer-token JSON API work against a database created before it
 * existed, without touching any existing rows.
 */
function ensureApiTokenColumn(PDO $pdo): void {
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $names = array_column($cols, 'name');
    if (!in_array('api_token', $names, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN api_token TEXT");
    }
}

function seedDatabase(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'hero',
            credits INTEGER NOT NULL DEFAULT 500,
            max_energy INTEGER NOT NULL DEFAULT 100,
            current_energy INTEGER NOT NULL DEFAULT 100,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $pdo->exec("
        CREATE TABLE gear (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,      -- suit | dampener | gadget
            description TEXT NOT NULL,
            price INTEGER NOT NULL,
            energy_cost INTEGER NOT NULL, -- energy drained per equip
            power_rating INTEGER NOT NULL, -- 1-100 flavor stat
            icon TEXT NOT NULL DEFAULT ''
        );
    ");

    $pdo->exec("
        CREATE TABLE inventory (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            gear_id INTEGER NOT NULL,
            equipped INTEGER NOT NULL DEFAULT 0,
            acquired_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (gear_id) REFERENCES gear(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE energy_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            change_amount INTEGER NOT NULL, -- positive = gain, negative = use
            reason TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $gear = [
        ['Kinetic Weave Suit',   'suit',      'Full-body suit that redistributes impact force. Reduces damage taken in combat.', 220, 15, 78, ''],
        ['Thermal Flux Jacket',  'suit',      'Regulates body heat for fire/ice-based powers, preventing energy overdraw.',       180, 10, 65, ''],
        ['Nano-Plating Armor',   'suit',      'Self-repairing armor plates woven with power-conductive nanofiber.',             340, 22, 90, ''],
        ['Null Cuffs',           'dampener',  'Wrist restraints that suppress offensive powers by up to 80%. Standard issue for containment units.', 150, 5, 60, ''],
        ['Signal Collar',        'dampener',  'Neck-worn device that mutes telepathic and telekinetic broadcasts in a 10m radius.', 200, 8, 55, ''],
        ['Overload Choker',      'dampener',  'Emergency dampener that forcibly caps energy output to prevent self-harm from overdraw.', 260, 12, 70, ''],
        ['Grapple Gauntlets',    'gadget',    'Wrist-mounted grapple line launcher, useful for both heroes and villains on the move.', 95,  4, 40, ''],
        ['Bio-Energy Cell',      'gadget',    'Portable rechargeable cell that stores excess bio-energy for later use.',         130, 0,  50, ''],
        ['Signal Jammer Disc',   'gadget',    'Throwable disc that disrupts nearby comms and tracking devices for 60 seconds.',  75,  6, 35, ''],
        ['Adaptive Visor',       'gadget',    'HUD visor that reads opponents\' power signatures in real time.',                 210, 9, 72, ''],
    ];
    $stmt = $pdo->prepare("INSERT INTO gear (name, category, description, price, energy_cost, power_rating, icon) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($gear as $g) {
        $stmt->execute($g);
    }
}
