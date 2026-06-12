<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/progression.php';

$pdo = ph_db();
$viewer = ph_user();

if ($viewer !== null) {
    ph_touch_daily($pdo, (int) $viewer['id']);
}

$requested = trim((string) ($_GET['u'] ?? ($viewer['username'] ?? '')));
if ($requested === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
$stmt->execute(['u' => $requested]);
$profile = $stmt->fetch();

$isOwn = $profile !== false && $viewer !== null && (int) $profile['id'] === (int) $viewer['id'];

// Changement de titre (profil personnel uniquement).
if ($isOwn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ph_csrf_check($_POST['csrf_token'] ?? null)) {
        ph_flash('error', 'Session expirée, merci de réessayer.');
    } else {
        $choice = trim((string) ($_POST['title'] ?? ''));
        $unlockedKeys = ph_unlocked_achievement_keys($pdo, (int) $profile['id']);
        $allowed = ph_available_titles($unlockedKeys);

        if ($choice === '' || in_array($choice, $allowed, true)) {
            $pdo->prepare('UPDATE users SET title = :t WHERE id = :id')
                ->execute(['t' => $choice === '' ? null : $choice, 'id' => (int) $profile['id']]);
            ph_flash('success', 'Titre mis à jour.');
        } else {
            ph_flash('error', 'Ce titre n\'est pas débloqué.');
        }
    }

    header('Location: profile.php');
    exit;
}

$flash = ph_pull_flash();

$unlocked = [];
$history = [];
$avgAccuracy = 0.0;
if ($profile !== false) {
    $rows = $pdo->prepare('SELECT achievement_key, unlocked_at FROM user_achievements WHERE user_id = :id');
    $rows->execute(['id' => (int) $profile['id']]);
    foreach ($rows->fetchAll() as $row) {
        $unlocked[$row['achievement_key']] = $row['unlocked_at'];
    }

    $hist = $pdo->prepare(
        'SELECT score, wave, kills, headshots, accuracy, combo, duration_seconds, created_at
         FROM scores WHERE user_id = :id ORDER BY created_at DESC, id DESC LIMIT 10'
    );
    $hist->execute(['id' => (int) $profile['id']]);
    $history = $hist->fetchAll();

    $acc = $pdo->prepare('SELECT COALESCE(AVG(accuracy), 0) FROM scores WHERE user_id = :id');
    $acc->execute(['id' => (int) $profile['id']]);
    $avgAccuracy = (float) $acc->fetchColumn();
}

function ph_playtime(int $seconds): string
{
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    return $hours > 0 ? sprintf('%d h %02d min', $hours, $minutes) : sprintf('%d min', $minutes);
}

$level = $profile !== false ? (int) $profile['level'] : 1;
$xp = $profile !== false ? (int) $profile['xp'] : 0;
$xpFloor = ph_xp_for_level($level);
$xpCeil = ph_xp_for_level($level + 1);
$xpPct = $xpCeil > $xpFloor ? max(0, min(100, (int) round(($xp - $xpFloor) * 100 / ($xpCeil - $xpFloor)))) : 100;

$shareUrl = '';
if ($profile !== false) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $shareUrl = $scheme . '://' . $host . '/profile.php?u=' . urlencode((string) $profile['username']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profile !== false ? 'Profil de ' . ph_h((string) $profile['username']) : 'Profil introuvable'; ?> - Pursuit Hunter</title>
    <style>
        :root {
            --bg: #070b12; --panel: rgba(12, 18, 31, 0.92); --line: rgba(125, 255, 178, 0.18);
            --accent: #7dffb2; --accent-2: #66e0ff; --warn: #ffd54d; --text: #edf2ff; --muted: #9bb0cc;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, rgba(102, 224, 255, 0.16), transparent 40%), linear-gradient(180deg, #0b1220 0%, #05070c 100%);
            color: var(--text); }
        .wrap { width: min(1080px, calc(100% - 24px)); margin: 0 auto; padding: 28px 0 48px; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #fff; background: rgba(255,255,255,0.08); border: none; font-weight: 700; cursor: pointer; }
        .btn.primary { background: linear-gradient(135deg, #ff5b5b, #cc0000); }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 20px; padding: 22px; margin-bottom: 18px; }
        .identity { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
        .avatar { width: 84px; height: 84px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 38px; font-weight: 900; background: linear-gradient(135deg, rgba(102,224,255,0.25), rgba(125,255,178,0.25));
            border: 2px solid var(--accent-2); }
        .identity h1 { margin: 0; font-size: 30px; letter-spacing: 1px; }
        .title-tag { color: var(--warn); font-size: 14px; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
        .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .chip { padding: 6px 12px; border-radius: 999px; font-size: 12px; letter-spacing: 1px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1); }
        .chip.accent { color: var(--accent); border-color: rgba(125,255,178,0.35); }
        .xpbar { margin-top: 14px; max-width: 420px; }
        .xpbar .bar { height: 12px; border-radius: 999px; background: rgba(255,255,255,0.08); overflow: hidden; }
        .xpbar .fill { height: 100%; background: linear-gradient(90deg, var(--accent-2), var(--accent)); }
        .xpbar .label { font-size: 12px; color: var(--muted); margin-top: 6px; letter-spacing: 1px; }
        h2 { margin: 0 0 14px; letter-spacing: 1px; font-size: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .stat { padding: 14px; border-radius: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.06); }
        .stat .n { font-size: 24px; font-weight: 900; color: var(--accent); }
        .stat .l { margin-top: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); }
        .badges { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px; }
        .badge { padding: 14px; border-radius: 14px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
        .badge.on { border-color: rgba(255, 213, 77, 0.45); background: rgba(255, 213, 77, 0.08); }
        .badge .ic { font-size: 26px; }
        .badge .nm { font-weight: 800; margin-top: 6px; }
        .badge .ds { font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }
        .badge.off { opacity: 0.55; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); border-bottom: 1px solid rgba(255,255,255,0.1); }
        tbody tr:nth-child(odd) { background: rgba(255,255,255,0.03); }
        td.score { color: var(--warn); font-weight: 800; }
        .share { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .share input { flex: 1; min-width: 220px; padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); color: var(--text); }
        select { padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: #0c121f; color: var(--text); }
        .flash { margin-bottom: 16px; padding: 14px 16px; border-radius: 14px; font-weight: 700; }
        .flash.success { background: rgba(125, 255, 178, 0.12); color: #9dffbf; border: 1px solid rgba(125, 255, 178, 0.22); }
        .flash.error { background: rgba(255, 107, 107, 0.12); color: #ffb3b3; border: 1px solid rgba(255, 107, 107, 0.22); }
        .empty { color: var(--muted); }
        @media (max-width: 640px) { .identity { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a class="btn" href="index.php">← Accueil</a>
            <div>
                <a class="btn" href="leaderboard.php">Leaderboard</a>
                <?php if ($viewer): ?>
                    <a class="btn primary" href="game.php">Jouer</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?php echo ph_h($flash['type']); ?>"><?php echo ph_h($flash['message']); ?></div>
        <?php endif; ?>

        <?php if ($profile === false): ?>
            <div class="panel">
                <h2>Profil introuvable</h2>
                <p class="empty">Aucun joueur ne porte le pseudo « <?php echo ph_h($requested); ?> ».</p>
            </div>
        <?php else: ?>
            <div class="panel identity">
                <div class="avatar"><?php echo ph_h(mb_strtoupper(mb_substr((string) $profile['username'], 0, 1))); ?></div>
                <div>
                    <h1><?php echo ph_h((string) $profile['username']); ?></h1>
                    <?php if (!empty($profile['title'])): ?>
                        <div class="title-tag">« <?php echo ph_h((string) $profile['title']); ?> »</div>
                    <?php endif; ?>
                    <div class="chips">
                        <span class="chip accent">Niveau <?php echo $level; ?></span>
                        <span class="chip">🪙 <?php echo number_format((int) $profile['coins'], 0, ',', ' '); ?> pièces</span>
                        <span class="chip">🔥 Série : <?php echo (int) $profile['current_streak']; ?> j (record <?php echo (int) $profile['best_streak']; ?>)</span>
                        <span class="chip">Inscrit le <?php echo ph_h(date('d/m/Y', strtotime((string) $profile['created_at']))); ?></span>
                    </div>
                    <div class="xpbar">
                        <div class="bar"><div class="fill" style="width: <?php echo $xpPct; ?>%;"></div></div>
                        <div class="label"><?php echo number_format($xp - $xpFloor, 0, ',', ' '); ?> / <?php echo number_format($xpCeil - $xpFloor, 0, ',', ' '); ?> XP vers le niveau <?php echo $level + 1; ?></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Statistiques</h2>
                <div class="stats">
                    <div class="stat"><div class="n"><?php echo number_format((int) $profile['best_score'], 0, ',', ' '); ?></div><div class="l">Meilleur score</div></div>
                    <div class="stat"><div class="n"><?php echo number_format((int) $profile['games_played'], 0, ',', ' '); ?></div><div class="l">Parties jouées</div></div>
                    <div class="stat"><div class="n"><?php echo ph_h(ph_playtime((int) $profile['playtime_seconds'])); ?></div><div class="l">Temps de jeu</div></div>
                    <div class="stat"><div class="n"><?php echo number_format((int) $profile['total_kills'], 0, ',', ' '); ?></div><div class="l">Éliminations</div></div>
                    <div class="stat"><div class="n"><?php echo number_format((int) $profile['total_headshots'], 0, ',', ' '); ?></div><div class="l">Headshots</div></div>
                    <div class="stat"><div class="n"><?php echo number_format((int) $profile['total_boss_kills'], 0, ',', ' '); ?></div><div class="l">Juggernauts</div></div>
                    <div class="stat"><div class="n"><?php echo number_format($avgAccuracy, 1, ',', ' '); ?> %</div><div class="l">Précision moyenne</div></div>
                </div>
            </div>

            <?php if ($isOwn): ?>
                <div class="panel">
                    <h2>Personnalisation</h2>
                    <form method="post" action="profile.php" class="share" style="margin-bottom: 14px;">
                        <input type="hidden" name="csrf_token" value="<?php echo ph_h(ph_csrf_token()); ?>">
                        <label for="title" style="color: var(--muted); font-size: 13px;">Titre affiché :</label>
                        <select id="title" name="title">
                            <option value="">— Aucun titre —</option>
                            <?php foreach (ph_available_titles(array_keys($unlocked)) as $titleOption): ?>
                                <option value="<?php echo ph_h($titleOption); ?>" <?php echo $profile['title'] === $titleOption ? 'selected' : ''; ?>>
                                    <?php echo ph_h($titleOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn primary" type="submit">Enregistrer</button>
                    </form>
                    <div class="share">
                        <label style="color: var(--muted); font-size: 13px;">Lien public :</label>
                        <input type="text" readonly value="<?php echo ph_h($shareUrl); ?>" onclick="this.select();">
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel">
                <h2>Succès (<?php echo count($unlocked); ?> / <?php echo count(ph_achievements()); ?>)</h2>
                <div class="badges">
                    <?php foreach (ph_achievements() as $key => $achievement): ?>
                        <?php $isUnlocked = isset($unlocked[$key]); ?>
                        <div class="badge <?php echo $isUnlocked ? 'on' : 'off'; ?>">
                            <div class="ic"><?php echo $isUnlocked || !$achievement['secret'] ? $achievement['icon'] : '❓'; ?></div>
                            <div class="nm"><?php echo $isUnlocked || !$achievement['secret'] ? ph_h($achievement['name']) : 'Succès secret'; ?></div>
                            <div class="ds">
                                <?php if ($isUnlocked): ?>
                                    <?php echo ph_h($achievement['desc']); ?><br>Débloqué le <?php echo ph_h(date('d/m/Y', strtotime((string) $unlocked[$key]))); ?>
                                <?php elseif ($achievement['secret']): ?>
                                    ??? Continue de jouer pour le découvrir.
                                <?php else: ?>
                                    <?php echo ph_h($achievement['desc']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <h2>Dernières parties</h2>
                <?php if ($history): ?>
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Score</th><th>Vague</th><th>Élim.</th><th>HS</th><th>Précision</th><th>Combo</th><th>Durée</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?php echo ph_h(date('d/m/Y H:i', strtotime((string) $row['created_at']))); ?></td>
                                    <td class="score"><?php echo number_format((int) $row['score'], 0, ',', ' '); ?></td>
                                    <td><?php echo (int) $row['wave']; ?></td>
                                    <td><?php echo (int) $row['kills']; ?></td>
                                    <td><?php echo (int) $row['headshots']; ?></td>
                                    <td><?php echo number_format((float) $row['accuracy'], 0); ?> %</td>
                                    <td>x<?php echo (int) $row['combo']; ?></td>
                                    <td><?php echo (int) $row['duration_seconds'] > 0 ? gmdate('i:s', (int) $row['duration_seconds']) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty">Aucune partie enregistrée pour le moment.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
