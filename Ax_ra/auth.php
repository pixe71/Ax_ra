<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/progression.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!ph_csrf_check($_POST['csrf_token'] ?? null)) {
    ph_flash('error', 'Session expirée, merci de réessayer.');
    header('Location: index.php#auth');
    exit;
}

if ($action !== 'login' && $action !== 'register') {
    ph_flash('error', 'Action invalide.');
    header('Location: index.php');
    exit;
}

if ($username === '' || strlen($username) < 3 || strlen($username) > 30) {
    ph_flash('error', 'Le pseudo doit contenir entre 3 et 30 caractères.');
    header('Location: index.php#auth');
    exit;
}

if ($action === 'register' && strlen($password) < 8) {
    ph_flash('error', 'Le mot de passe doit faire au moins 8 caractères.');
    header('Location: index.php#auth');
    exit;
}

if ($password === '') {
    ph_flash('error', 'Le mot de passe est requis.');
    header('Location: index.php#auth');
    exit;
}

$pdo = ph_db();
$ip = ph_client_ip();

try {
    if ($action === 'register') {
        if (!ph_rate_limit($pdo, 'register:' . $ip, 5, 3600)) {
            ph_flash('error', 'Trop de créations de compte, réessaie plus tard.');
            header('Location: index.php#auth');
            exit;
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $check->execute(['username' => $username]);

        if ($check->fetchColumn()) {
            ph_flash('error', 'Ce pseudo existe déjà.');
            header('Location: index.php#auth');
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
        $stmt->execute([
            'username' => $username,
            'password_hash' => ph_password_hash($password),
        ]);

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $pdo->lastInsertId(),
            'username' => $username,
        ];

        ph_touch_daily($pdo, (int) $_SESSION['user']['id']);
        ph_flash('success', 'Compte créé avec succès.');
        header('Location: index.php');
        exit;
    }

    if (!ph_rate_limit($pdo, 'login:' . $ip, 10, 300)) {
        ph_flash('error', 'Trop de tentatives de connexion, réessaie dans quelques minutes.');
        header('Location: index.php#auth');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        ph_flash('error', 'Identifiants invalides.');
        header('Location: index.php#auth');
        exit;
    }

    if (ph_password_needs_rehash((string) $user['password_hash'])) {
        $rehash = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $rehash->execute(['hash' => ph_password_hash($password), 'id' => (int) $user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
    ];

    ph_touch_daily($pdo, (int) $user['id']);
    ph_flash('success', 'Connexion réussie.');
    header('Location: index.php');
    exit;
} catch (Throwable $exception) {
    ph_flash('error', 'Erreur serveur, merci de réessayer.');
    header('Location: index.php#auth');
    exit;
}
