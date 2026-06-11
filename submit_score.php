<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

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

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$score = max(0, (int) ($payload['score'] ?? 0));
$wave = max(0, (int) ($payload['wave'] ?? 0));
$kills = max(0, (int) ($payload['kills'] ?? 0));
$headshots = max(0, (int) ($payload['headshots'] ?? 0));
$accuracy = max(0, (float) ($payload['accuracy'] ?? 0));
$combo = max(0, (int) ($payload['combo'] ?? 0));
$bossKills = max(0, (int) ($payload['bossKills'] ?? 0));
$challengesDone = max(0, (int) ($payload['challengesDone'] ?? 0));

$pdo = ph_db();
$userId = (int) ph_user()['id'];

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO scores (user_id, score, wave, kills, headshots, accuracy, combo, boss_kills, challenges_done)
         VALUES (:user_id, :score, :wave, :kills, :headshots, :accuracy, :combo, :boss_kills, :challenges_done)'
    );
    $insert->execute([
        'user_id' => $userId,
        'score' => $score,
        'wave' => $wave,
        'kills' => $kills,
        'headshots' => $headshots,
        'accuracy' => $accuracy,
        'combo' => $combo,
        'boss_kills' => $bossKills,
        'challenges_done' => $challengesDone,
    ]);

    $best = $pdo->prepare('SELECT best_score FROM users WHERE id = :id FOR UPDATE');
    $best->execute(['id' => $userId]);
    $currentBest = (int) $best->fetchColumn();

    $newBest = max($currentBest, $score);
    if ($newBest !== $currentBest) {
        $update = $pdo->prepare('UPDATE users SET best_score = :best_score WHERE id = :id');
        $update->execute([
            'best_score' => $newBest,
            'id' => $userId,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $newBest > $currentBest ? 'Nouveau record enregistré.' : 'Score enregistré.',
        'bestScore' => $newBest,
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
