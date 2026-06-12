<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/progression.php';

$pdo = ph_db();
$user = ph_user();

if ($user !== null) {
    ph_touch_daily($pdo, (int) $user['id']);
}

$view = (string) ($_GET['view'] ?? 'global');
if (!in_array($view, ['global', 'week', 'me'], true) || ($view === 'me' && $user === null)) {
    $view = 'global';
}

$rows = [];
$startRank = 1;

if ($view === 'week') {
    $stmt = $pdo->prepare(
        'SELECT u.username, u.level, u.title, MAX(s.score) AS best_score
         FROM scores s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.created_at >= :since AND s.score > 0
         GROUP BY s.user_id, u.username, u.level, u.title
         ORDER BY best_score DESC
         LIMIT 50'
    );
    $stmt->execute(['since' => date('Y-m-d H:i:s', strtotime('-7 days'))]);
    $rows = $stmt->fetchAll();
} elseif ($view === 'me') {
    $mine = $pdo->prepare('SELECT best_score FROM users WHERE id = :id');
    $mine->execute(['id' => (int) $user['id']]);
    $myBest = (int) $mine->fetchColumn();

    $rank = $pdo->prepare('SELECT COUNT(*) FROM users WHERE best_score > :s AND best_score > 0');
    $rank->execute(['s' => $myBest]);
    $myRank = (int) $rank->fetchColumn() + 1;

    $offset = max(0, $myRank - 6);
    $stmt = $pdo->prepare(
        'SELECT username, level, title, best_score
         FROM users
         WHERE best_score > 0
         ORDER BY best_score DESC, created_at ASC
         LIMIT 11 OFFSET ' . $offset
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $startRank = $offset + 1;
} else {
    $rows = $pdo->query(
        'SELECT username, level, title, best_score
         FROM users
         WHERE best_score > 0
         ORDER BY best_score DESC, created_at ASC
         LIMIT 50'
    )->fetchAll();
}
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
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #fff; background: rgba(255,255,255,0.08); }
        .btn.primary { background: linear-gradient(135deg, #ff5b5b, #cc0000); }
        .card { background: rgba(12,18,31,0.92); border: 1px solid rgba(125,255,178,0.18); border-radius: 20px; overflow: hidden; }
        .head { padding: 20px 22px 8px; }
        .head h1 { margin: 0; }
        .head p { margin: 8px 0 0; color: #9bb0cc; }
        .tabs { display: flex; gap: 8px; padding: 14px 22px 0; flex-wrap: wrap; }
        .tab { padding: 10px 16px; border-radius: 999px; text-decoration: none; color: #9bb0cc; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); font-size: 14px; font-weight: 700; }
        .tab.on { color: #05070c; background: linear-gradient(135deg, #66e0ff, #7dffb2); border-color: transparent; }
        .list { padding: 14px 22px 22px; display: grid; gap: 10px; }
        .row { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 14px 16px; border-radius: 16px; background: rgba(255,255,255,0.05); }
        .row.me { border: 1px solid rgba(255, 213, 77, 0.5); background: rgba(255, 213, 77, 0.08); }
        .name { font-weight: 800; }
        .name a { color: inherit; text-decoration: none; }
        .name a:hover { color: #66e0ff; }
        .lvl { display: inline-block; margin-left: 8px; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; color: #7dffb2; background: rgba(125,255,178,0.12); border: 1px solid rgba(125,255,178,0.3); }
        .meta { font-size: 12px; color: #9bb0cc; margin-top: 4px; }
        .meta .title { color: #ffd54d; }
        .score { font-size: 24px; font-weight: 900; color: #ffd54d; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a class="btn" href="index.php">Accueil</a>
            <div>
                <?php if ($user): ?>
                    <a class="btn" href="profile.php">Mon profil</a>
                    <a class="btn primary" href="game.php">Jouer</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="head">
                <h1>Leaderboard</h1>
                <p><?php echo $view === 'week' ? 'Meilleurs scores des 7 derniers jours.' : ($view === 'me' ? 'Ton classement et tes voisins directs.' : 'Classement des meilleurs scores de tous les temps.'); ?></p>
            </div>
            <div class="tabs">
                <a class="tab <?php echo $view === 'global' ? 'on' : ''; ?>" href="leaderboard.php?view=global">🌍 Global</a>
                <a class="tab <?php echo $view === 'week' ? 'on' : ''; ?>" href="leaderboard.php?view=week">📅 Cette semaine</a>
                <?php if ($user): ?>
                    <a class="tab <?php echo $view === 'me' ? 'on' : ''; ?>" href="leaderboard.php?view=me">🎯 Autour de moi</a>
                <?php endif; ?>
            </div>
            <div class="list">
                <?php if ($rows): ?>
                    <?php foreach ($rows as $position => $row): ?>
                        <?php $isMe = $user !== null && $row['username'] === $user['username']; ?>
                        <div class="row <?php echo $isMe ? 'me' : ''; ?>">
                            <div>
                                <div class="name">
                                    #<?php echo $startRank + $position; ?>
                                    <a href="profile.php?u=<?php echo urlencode((string) $row['username']); ?>"><?php echo ph_h((string) $row['username']); ?></a>
                                    <span class="lvl">Niv. <?php echo (int) ($row['level'] ?? 1); ?></span>
                                </div>
                                <div class="meta">
                                    <?php if (!empty($row['title'])): ?>
                                        <span class="title">« <?php echo ph_h((string) $row['title']); ?> »</span>
                                    <?php else: ?>
                                        Sans titre
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="score"><?php echo number_format((int) $row['best_score'], 0, ',', ' '); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row">
                        <div>
                            <div class="name">Aucun score pour le moment</div>
                            <div class="meta"><?php echo $view === 'week' ? 'Aucune partie jouée cette semaine.' : 'Connecte-toi et lance une partie pour ouvrir le classement.'; ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
