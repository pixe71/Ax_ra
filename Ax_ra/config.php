<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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

function ph_ensure_column(PDO $pdo, string $table, string $column, string $ddl): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute(['t' => $table, 'c' => $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec(sprintf('ALTER TABLE `%s` ADD COLUMN %s', $table, $ddl));
    }
}

function ph_ensure_index(PDO $pdo, string $table, string $index, string $ddl): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i'
    );
    $stmt->execute(['t' => $table, 'i' => $index]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec(sprintf('ALTER TABLE `%s` ADD %s', $table, $ddl));
    }
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
            duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_scores_user_id (user_id),
            KEY idx_scores_score (score),
            KEY idx_scores_created_at (created_at),
            CONSTRAINT fk_scores_users
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_achievements (
            user_id INT UNSIGNED NOT NULL,
            achievement_key VARCHAR(40) NOT NULL,
            unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, achievement_key),
            CONSTRAINT fk_achievements_users
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_quests (
            user_id INT UNSIGNED NOT NULL,
            quest_date DATE NOT NULL,
            quest_key VARCHAR(40) NOT NULL,
            progress INT UNSIGNED NOT NULL DEFAULT 0,
            completed_at DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (user_id, quest_date, quest_key),
            CONSTRAINT fk_quests_users
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS rate_limits (
            rl_key VARCHAR(80) NOT NULL,
            window_start INT UNSIGNED NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (rl_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Migrations idempotentes pour les bases créées avant la progression.
    ph_ensure_column($pdo, 'users', 'xp', 'xp INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'level', 'level INT UNSIGNED NOT NULL DEFAULT 1');
    ph_ensure_column($pdo, 'users', 'coins', 'coins INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'title', 'title VARCHAR(40) NULL DEFAULT NULL');
    ph_ensure_column($pdo, 'users', 'games_played', 'games_played INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'playtime_seconds', 'playtime_seconds INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'total_kills', 'total_kills INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'total_headshots', 'total_headshots INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'total_boss_kills', 'total_boss_kills INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'current_streak', 'current_streak INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'best_streak', 'best_streak INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_column($pdo, 'users', 'last_login_date', 'last_login_date DATE NULL DEFAULT NULL');
    ph_ensure_column($pdo, 'scores', 'duration_seconds', 'duration_seconds INT UNSIGNED NOT NULL DEFAULT 0');
    ph_ensure_index($pdo, 'scores', 'idx_scores_created_at', 'KEY idx_scores_created_at (created_at)');
}

function ph_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Valeurs par défaut pensées pour XAMPP/WAMP en local (MySQL sur
    // 127.0.0.1, utilisateur root sans mot de passe). En production ou
    // sous Docker, définir les variables d'environnement DB_*.
    $host = ph_config('DB_HOST', '127.0.0.1');
    $port = ph_config('DB_PORT', '3306');
    $name = ph_config('DB_NAME', 'pursuit_hunter');
    $user = ph_config('DB_USER', 'root');
    $pass = ph_config('DB_PASSWORD', '');

    try {
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $server = ph_pdo($serverDsn, $user, $pass);
        $server->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            str_replace('`', '``', $name)
        ));

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = ph_pdo($dsn, $user, $pass);
        ph_initialize_schema($pdo);
    } catch (PDOException $exception) {
        http_response_code(500);
        // Pas de re-throw : la stack trace exposerait les identifiants.
        exit(sprintf(
            '<h1>Base de données inaccessible</h1>' .
            '<p>Connexion MySQL impossible sur <strong>%s:%s</strong> (utilisateur <strong>%s</strong>).</p>' .
            '<ul>' .
            '<li>Sous XAMPP : démarre le module <strong>MySQL</strong> dans le panneau de contrôle.</li>' .
            '<li>Sinon : configure les variables d\'environnement DB_HOST, DB_PORT, DB_NAME, DB_USER et DB_PASSWORD.</li>' .
            '</ul><p>Détail : %s</p>',
            ph_h((string) $host),
            ph_h((string) $port),
            ph_h((string) $user),
            ph_h($exception->getCode() === 1045 ? 'identifiants refusés' : $exception->getMessage())
        ));
    }

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

function ph_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function ph_csrf_check(?string $token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function ph_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/**
 * Limiteur de requêtes par clé (action + IP ou id utilisateur).
 * Retourne false quand le quota de la fenêtre est dépassé.
 */
function ph_rate_limit(PDO $pdo, string $key, int $maxHits, int $windowSeconds): bool
{
    $now = time();
    $cutoff = $now - $windowSeconds;

    $stmt = $pdo->prepare(
        'INSERT INTO rate_limits (rl_key, window_start, hits) VALUES (:k, :now_a, 1)
         ON DUPLICATE KEY UPDATE
            hits = IF(window_start < :cut_a, 1, hits + 1),
            window_start = IF(window_start < :cut_b, :now_b, window_start)'
    );
    $stmt->execute([
        'k' => $key,
        'now_a' => $now,
        'now_b' => $now,
        'cut_a' => $cutoff,
        'cut_b' => $cutoff,
    ]);

    $check = $pdo->prepare('SELECT hits FROM rate_limits WHERE rl_key = :k');
    $check->execute(['k' => $key]);

    return (int) $check->fetchColumn() <= $maxHits;
}

function ph_password_hash(string $password): string
{
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    return password_hash($password, PASSWORD_DEFAULT);
}

function ph_password_needs_rehash(string $hash): bool
{
    if (defined('PASSWORD_ARGON2ID')) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }

    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}
