<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ph_config(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function ph_pdo(string $dsn, string $user, string $pass): PDO
{
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function ph_initialize_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(30) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            best_score INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_users_username (username),
            KEY idx_users_best_score (best_score)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS scores (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            score INT UNSIGNED NOT NULL DEFAULT 0,
            wave INT UNSIGNED NOT NULL DEFAULT 0,
            kills INT UNSIGNED NOT NULL DEFAULT 0,
            headshots INT UNSIGNED NOT NULL DEFAULT 0,
            accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            combo INT UNSIGNED NOT NULL DEFAULT 0,
            boss_kills INT UNSIGNED NOT NULL DEFAULT 0,
            challenges_done INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_scores_user_id (user_id),
            KEY idx_scores_score (score),
            CONSTRAINT fk_scores_users
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ph_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = ph_config('DB_HOST', 'db');
    $port = ph_config('DB_PORT', '3306');
    $name = ph_config('DB_NAME', 'pursuit_hunter');
    $user = ph_config('DB_USER', 'root');
    $pass = ph_config('DB_PASSWORD', '');

    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
    $server = ph_pdo($serverDsn, $user, $pass);
    $server->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $name)
    ));

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $pdo = ph_pdo($dsn, $user, $pass);
    ph_initialize_schema($pdo);

    return $pdo;
}

function ph_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function ph_is_logged_in(): bool
{
    return ph_user() !== null;
}

function ph_require_login(): void
{
    if (!ph_is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function ph_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function ph_pull_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function ph_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
