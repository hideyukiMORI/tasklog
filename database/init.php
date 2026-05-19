<?php

declare(strict_types=1);

$dbPath = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? __DIR__ . '/../data/tasklog.db';

$dir = dirname((string) $dbPath);

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS users (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        email        TEXT    NOT NULL UNIQUE,
        password_hash TEXT   NOT NULL,
        created_at   TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ', 'now'))
    )
SQL);

$pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS tasks (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL,
        title       TEXT    NOT NULL,
        description TEXT    NOT NULL DEFAULT '',
        status      TEXT    NOT NULL DEFAULT 'open',
        created_at  TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ', 'now')),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
SQL);

echo "Database initialized at: {$dbPath}\n";
