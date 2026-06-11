<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = ph_db();
$user = ph_user();
$rows = $pdo->query(
    'SELECT u.username, u.best_score, u.created_at
     FROM users u
     WHERE u.best_score > 0
     ORDER BY u.best_score DESC, u.created_at ASC
     LIMIT 50'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Pursuit Hunter</title>
    <style>
        body { margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #05070c; color: #edf2ff; }
        .wrap { width: min(980px, calc(100% - 24px)); margin: 0 auto; padding: 28px 0 40px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; }
        .btn { display: inline-block; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #fff; background: rgba(255,255,255,0.08); }
        .btn.primary { background: linear-gradient(135deg, #ff5b5b, #cc0000); }
        .card { background: rgba(12,18,31,0.92); border: 1px solid rgba(125,255,178,0.18); border-radius: 20px; overflow: hidden; }
        .head { padding: 20px 22px 8px; }
        .head h1 { margin: 0; }
        .head p { margin: 8px 0 0; color: #9bb0cc; }
        .list { padding: 14px 22px 22px; display: grid; gap: 10px; }
        .row { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 14px 16px; border-radius: 16px; background: rgba(255,255,255,0.05); }
        .name { font-weight: 800; }
        .meta { font-size: 12px; color: #9bb0cc; margin-top: 4px; }
        .score { font-size: 24px; font-weight: 900; color: #ffd54d; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a class="btn" href="index.php">Accueil</a>
            <?php if ($user): ?>
                <a class="btn primary" href="game.php">Jouer</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="head">
                <h1>Leaderboard</h1>
                <p>Classement des meilleurs scores enregistrés sur le serveur.</p>
            </div>
            <div class="list">
                <?php if ($rows): ?>
                    <?php foreach ($rows as $position => $row): ?>
                        <div class="row">
                            <div>
                                <div class="name">#<?php echo $position + 1; ?> <?php echo ph_h($row['username']); ?></div>
                                <div class="meta">Inscrit le <?php echo ph_h(date('d/m/Y', strtotime($row['created_at']))); ?></div>
                            </div>
                            <div class="score"><?php echo number_format((int) $row['best_score'], 0, ',', ' '); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row">
                        <div>
                            <div class="name">Aucun score pour le moment</div>
                            <div class="meta">Connecte-toi et lance une partie pour ouvrir le classement.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
