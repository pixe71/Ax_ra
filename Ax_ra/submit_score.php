<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/progression.php';

header('Content-Type: application/json; charset=utf-8');

if (!ph_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!ph_csrf_check(is_string($csrfHeader) ? $csrfHeader : null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Jeton de sécurité invalide, recharge la page du jeu.']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$run = [
    'score' => max(0, (int) ($payload['score'] ?? 0)),
    'wave' => max(0, (int) ($payload['wave'] ?? 0)),
    'kills' => max(0, (int) ($payload['kills'] ?? 0)),
    'headshots' => max(0, (int) ($payload['headshots'] ?? 0)),
    'accuracy' => max(0.0, (float) ($payload['accuracy'] ?? 0)),
    'combo' => max(0, (int) ($payload['combo'] ?? $payload['bestCombo'] ?? 0)),
    'boss_kills' => max(0, (int) ($payload['bossKills'] ?? 0)),
    'challenges_done' => max(0, (int) ($payload['challengesDone'] ?? 0)),
    'duration_seconds' => max(0, (int) ($payload['durationSeconds'] ?? 0)),
];

if (!empty($payload['practice'])) {
    echo json_encode([
        'success' => true,
        'practice' => true,
        'message' => 'Mode entraînement : score non enregistré.',
    ]);
    exit;
}

$pdo = ph_db();
$userId = (int) ph_user()['id'];

if (!ph_rate_limit($pdo, 'score:' . $userId, 30, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Trop de scores envoyés, ralentis un peu.']);
    exit;
}

$invalid = ph_validate_run($run);
if ($invalid !== null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Score rejeté par la validation serveur : ' . $invalid]);
    exit;
}

ph_touch_daily($pdo, $userId);

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO scores (user_id, score, wave, kills, headshots, accuracy, combo, boss_kills, challenges_done, duration_seconds)
         VALUES (:user_id, :score, :wave, :kills, :headshots, :accuracy, :combo, :boss_kills, :challenges_done, :duration_seconds)'
    );
    $insert->execute([
        'user_id' => $userId,
        'score' => $run['score'],
        'wave' => $run['wave'],
        'kills' => $run['kills'],
        'headshots' => $run['headshots'],
        'accuracy' => min(100, $run['accuracy']),
        'combo' => $run['combo'],
        'boss_kills' => $run['boss_kills'],
        'challenges_done' => $run['challenges_done'],
        'duration_seconds' => $run['duration_seconds'],
    ]);

    $best = $pdo->prepare('SELECT best_score FROM users WHERE id = :id FOR UPDATE');
    $best->execute(['id' => $userId]);
    $currentBest = (int) $best->fetchColumn();

    $newBest = max($currentBest, $run['score']);
    if ($newBest !== $currentBest) {
        $update = $pdo->prepare('UPDATE users SET best_score = :best_score WHERE id = :id');
        $update->execute([
            'best_score' => $newBest,
            'id' => $userId,
        ]);
    }

    $rewards = ph_apply_game_results($pdo, $userId, $run);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $newBest > $currentBest ? 'Nouveau record enregistré.' : 'Score enregistré.',
        'bestScore' => $newBest,
        'newBest' => $newBest > $currentBest,
        'rewards' => [
            'xp' => $rewards['xp'],
            'coins' => $rewards['coins'],
            'level' => $rewards['level'],
            'levelUp' => $rewards['levelUp'],
            'achievements' => array_map(
                fn(array $a): string => $a['icon'] . ' ' . $a['name'],
                $rewards['achievements']
            ),
            'quests' => array_map(
                fn(array $q): string => $q['name'],
                $rewards['quests']
            ),
        ],
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'enregistrement du score.',
    ]);
}
