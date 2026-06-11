<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

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

if ($password === '' || strlen($password) < 4) {
    ph_flash('error', 'Le mot de passe doit faire au moins 4 caractères.');
    header('Location: index.php#auth');
    exit;
}

$pdo = ph_db();

try {
    if ($action === 'register') {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $check->execute(['username' => $username]);

        if ($check->fetchColumn()) {
            ph_flash('error', 'Ce pseudo existe déjà.');
            header('Location: index.php#auth');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
        $stmt->execute([
            'username' => $username,
            'password_hash' => $hash,
        ]);

        $_SESSION['user'] = [
            'id' => (int) $pdo->lastInsertId(),
            'username' => $username,
        ];

        ph_flash('success', 'Compte créé avec succès.');
        header('Location: index.php');
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

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
    ];

    ph_flash('success', 'Connexion réussie.');
    header('Location: index.php');
    exit;
} catch (Throwable $exception) {
    ph_flash('error', 'Erreur serveur : ' . $exception->getMessage());
    header('Location: index.php#auth');
    exit;
}
