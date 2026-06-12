<?php

declare(strict_types=1);

/**
 * Moteur de progression : XP, niveaux, pièces, succès, titres,
 * quêtes journalières et séries de connexion.
 */

const PH_XP_GAME_CAP = 20000;
const PH_DAILY_REWARD_BASE = 10;
const PH_DAILY_REWARD_STEP = 5;
const PH_DAILY_REWARD_MAX = 60;

/** XP cumulée requise pour atteindre un niveau donné. */
function ph_xp_for_level(int $level): int
{
    return 75 * ($level - 1) * $level;
}

/** Niveau correspondant à un total d'XP. */
function ph_level_for_xp(int $xp): int
{
    // Epsilon : aux paliers exacts, sqrt peut rendre 198,99999… au lieu de 199.
    $level = (int) floor((1 + sqrt(1 + ($xp / 75) * 4)) / 2 + 1e-9);
    return max(1, $level);
}

/** XP gagnée pour une partie validée. */
function ph_xp_for_run(array $run): int
{
    $xp = intdiv($run['score'], 100) + $run['wave'] * 20 + $run['kills'] * 3;
    return min(PH_XP_GAME_CAP, max(0, $xp));
}

/** Pièces gagnées pour une partie validée. */
function ph_coins_for_run(array $run): int
{
    return 10 + intdiv($run['score'], 250) + $run['boss_kills'] * 25;
}

/**
 * Catalogue des succès. Conditions évaluées sur le profil mis à jour
 * ($u) et la partie qui vient d'être jouée ($run, peut être vide).
 * Les succès "secret" restent masqués tant qu'ils ne sont pas débloqués.
 */
function ph_achievements(): array
{
    return [
        'first_blood' => ['name' => 'Première traque', 'desc' => 'Jouer sa première partie.', 'icon' => '🎯', 'coins' => 25, 'title' => null, 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['games_played'] >= 1],
        'score_10k' => ['name' => 'Chasseur confirmé', 'desc' => 'Atteindre 10 000 points en une partie.', 'icon' => '🏅', 'coins' => 50, 'title' => 'Chasseur', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['best_score'] >= 10000],
        'score_50k' => ['name' => 'As de la gâchette', 'desc' => 'Atteindre 50 000 points en une partie.', 'icon' => '🥇', 'coins' => 150, 'title' => 'As de la gâchette', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['best_score'] >= 50000],
        'score_150k' => ['name' => 'Légende de la traque', 'desc' => 'Atteindre 150 000 points en une partie.', 'icon' => '👑', 'coins' => 300, 'title' => 'Légende', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['best_score'] >= 150000],
        'wave_5' => ['name' => 'Survivant', 'desc' => 'Atteindre la vague 5.', 'icon' => '🌊', 'coins' => 40, 'title' => null, 'secret' => false,
            'check' => fn(array $u, array $run): bool => ($run['wave'] ?? 0) >= 5],
        'wave_10' => ['name' => 'Increvable', 'desc' => 'Atteindre la vague 10.', 'icon' => '🛡️', 'coins' => 100, 'title' => 'Increvable', 'secret' => false,
            'check' => fn(array $u, array $run): bool => ($run['wave'] ?? 0) >= 10],
        'wave_20' => ['name' => 'Maître Tacticien', 'desc' => 'Atteindre la vague 20.', 'icon' => '⚔️', 'coins' => 250, 'title' => 'Maître Tacticien', 'secret' => false,
            'check' => fn(array $u, array $run): bool => ($run['wave'] ?? 0) >= 20],
        'kills_500' => ['name' => 'Exterminateur', 'desc' => 'Éliminer 500 ennemis au total.', 'icon' => '💥', 'coins' => 75, 'title' => null, 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['total_kills'] >= 500],
        'kills_2500' => ['name' => 'Fléau', 'desc' => 'Éliminer 2 500 ennemis au total.', 'icon' => '☄️', 'coins' => 200, 'title' => 'Fléau', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['total_kills'] >= 2500],
        'hs_100' => ['name' => "Tireur d'élite", 'desc' => 'Réussir 100 headshots au total.', 'icon' => '🎯', 'coins' => 100, 'title' => "Tireur d'élite", 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['total_headshots'] >= 100],
        'sharpshooter' => ['name' => 'Œil de faucon', 'desc' => 'Finir une partie à 90 % de précision avec au moins 30 éliminations.', 'icon' => '🦅', 'coins' => 150, 'title' => 'Œil de faucon', 'secret' => false,
            'check' => fn(array $u, array $run): bool => ($run['accuracy'] ?? 0) >= 90 && ($run['kills'] ?? 0) >= 30],
        'boss_10' => ['name' => 'Tueur de Juggernaut', 'desc' => 'Abattre 10 Juggernauts au total.', 'icon' => '☠️', 'coins' => 200, 'title' => 'Tueur de Juggernaut', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['total_boss_kills'] >= 10],
        'combo_50' => ['name' => 'Inarrêtable', 'desc' => 'Atteindre un combo de 50.', 'icon' => '🔥', 'coins' => 120, 'title' => null, 'secret' => false,
            'check' => fn(array $u, array $run): bool => ($run['combo'] ?? 0) >= 50],
        'streak_7' => ['name' => 'Fidèle au poste', 'desc' => 'Se connecter 7 jours de suite.', 'icon' => '📅', 'coins' => 150, 'title' => 'Fidèle au poste', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['best_streak'] >= 7],
        'level_10' => ['name' => 'Vétéran', 'desc' => 'Atteindre le niveau 10.', 'icon' => '⭐', 'coins' => 200, 'title' => 'Vétéran', 'secret' => false,
            'check' => fn(array $u, array $run): bool => $u['level'] >= 10],
        'secret_perfect' => ['name' => 'Sans bavure', 'desc' => 'Finir une partie à 100 % de précision avec au moins 20 éliminations.', 'icon' => '💎', 'coins' => 250, 'title' => 'Sans bavure', 'secret' => true,
            'check' => fn(array $u, array $run): bool => ($run['accuracy'] ?? 0) >= 100 && ($run['kills'] ?? 0) >= 20],
        'secret_night' => ['name' => 'Oiseau de nuit', 'desc' => 'Finir une partie entre 3 h et 5 h du matin.', 'icon' => '🦉', 'coins' => 100, 'title' => null, 'secret' => true,
            'check' => fn(array $u, array $run): bool => !empty($run['played']) && in_array((int) date('G'), [3, 4], true)],
    ];
}

/** Titres sélectionnables : niveau 1 + ceux débloqués par les succès. */
function ph_available_titles(array $unlockedKeys): array
{
    $titles = ['Recrue'];

    foreach (ph_achievements() as $key => $achievement) {
        if ($achievement['title'] !== null && in_array($key, $unlockedKeys, true)) {
            $titles[] = $achievement['title'];
        }
    }

    return $titles;
}

function ph_unlocked_achievement_keys(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT achievement_key FROM user_achievements WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);

    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Déverrouille les succès nouvellement validés et crédite leurs pièces.
 * Retourne la liste des succès débloqués pendant cet appel.
 */
function ph_grant_achievements(PDO $pdo, int $userId, array $userRow, array $run = []): array
{
    $unlocked = ph_unlocked_achievement_keys($pdo, $userId);
    $insert = $pdo->prepare('INSERT IGNORE INTO user_achievements (user_id, achievement_key) VALUES (:id, :k)');
    $granted = [];
    $coins = 0;

    foreach (ph_achievements() as $key => $achievement) {
        if (in_array($key, $unlocked, true) || !$achievement['check']($userRow, $run)) {
            continue;
        }

        $insert->execute(['id' => $userId, 'k' => $key]);
        if ($insert->rowCount() > 0) {
            $granted[] = ['key' => $key, 'name' => $achievement['name'], 'icon' => $achievement['icon'], 'coins' => $achievement['coins']];
            $coins += $achievement['coins'];
        }
    }

    if ($coins > 0) {
        $pdo->prepare('UPDATE users SET coins = coins + :c WHERE id = :id')
            ->execute(['c' => $coins, 'id' => $userId]);
    }

    return $granted;
}

/** Catalogue des quêtes journalières. */
function ph_quest_pool(): array
{
    return [
        'play_3' => ['name' => 'Jouer 3 parties', 'target' => 3, 'mode' => 'sum', 'metric' => 'games', 'coins' => 60, 'xp' => 250],
        'score_15k' => ['name' => 'Marquer 15 000 points (cumulés)', 'target' => 15000, 'mode' => 'sum', 'metric' => 'score', 'coins' => 80, 'xp' => 300],
        'kills_150' => ['name' => 'Éliminer 150 ennemis (cumulés)', 'target' => 150, 'mode' => 'sum', 'metric' => 'kills', 'coins' => 70, 'xp' => 250],
        'wave_8' => ['name' => 'Atteindre la vague 8', 'target' => 8, 'mode' => 'max', 'metric' => 'wave', 'coins' => 90, 'xp' => 350],
        'hs_40' => ['name' => 'Réussir 40 headshots (cumulés)', 'target' => 40, 'mode' => 'sum', 'metric' => 'headshots', 'coins' => 80, 'xp' => 300],
        'boss_2' => ['name' => 'Abattre 2 Juggernauts', 'target' => 2, 'mode' => 'sum', 'metric' => 'boss_kills', 'coins' => 100, 'xp' => 400],
    ];
}

/** Les 3 quêtes du jour, choisies de façon déterministe par la date. */
function ph_daily_quests(?string $date = null): array
{
    $date ??= date('Y-m-d');
    $pool = ph_quest_pool();
    $keys = array_keys($pool);
    $seed = crc32('pursuit-quests-' . $date);

    $picked = [];
    for ($i = 0; $i < 3 && $keys !== []; $i++) {
        $index = ($seed >> ($i * 7)) % count($keys);
        $picked[$keys[$index]] = $pool[$keys[$index]];
        array_splice($keys, $index, 1);
    }

    return $picked;
}

/** Progression du joueur sur les quêtes du jour (création des lignes au besoin). */
function ph_user_quests(PDO $pdo, int $userId, ?string $date = null): array
{
    $date ??= date('Y-m-d');
    $quests = ph_daily_quests($date);

    $stmt = $pdo->prepare(
        'SELECT quest_key, progress, completed_at FROM user_quests
         WHERE user_id = :id AND quest_date = :d'
    );
    $stmt->execute(['id' => $userId, 'd' => $date]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[$row['quest_key']] = $row;
    }

    $result = [];
    foreach ($quests as $key => $quest) {
        $row = $rows[$key] ?? null;
        $result[$key] = $quest + [
            'progress' => $row === null ? 0 : min((int) $row['progress'], $quest['target']),
            'completed' => $row !== null && $row['completed_at'] !== null,
        ];
    }

    return $result;
}

/**
 * Met à jour les quêtes du jour après une partie.
 * Retourne les quêtes terminées par cette partie (récompenses créditées).
 */
function ph_update_quests(PDO $pdo, int $userId, array $run): array
{
    $date = date('Y-m-d');
    $metrics = [
        'games' => 1,
        'score' => $run['score'],
        'kills' => $run['kills'],
        'wave' => $run['wave'],
        'headshots' => $run['headshots'],
        'boss_kills' => $run['boss_kills'],
    ];

    $completed = [];
    $coins = 0;
    $xp = 0;

    foreach (ph_user_quests($pdo, $userId, $date) as $key => $quest) {
        if ($quest['completed']) {
            continue;
        }

        $value = (int) ($metrics[$quest['metric']] ?? 0);
        $progress = $quest['mode'] === 'max'
            ? max($quest['progress'], $value)
            : $quest['progress'] + $value;
        $done = $progress >= $quest['target'];

        $stmt = $pdo->prepare(
            'INSERT INTO user_quests (user_id, quest_date, quest_key, progress, completed_at)
             VALUES (:id, :d, :k, :p, :c)
             ON DUPLICATE KEY UPDATE progress = VALUES(progress), completed_at = VALUES(completed_at)'
        );
        $stmt->execute([
            'id' => $userId,
            'd' => $date,
            'k' => $key,
            'p' => min($progress, 4294967295),
            'c' => $done ? date('Y-m-d H:i:s') : null,
        ]);

        if ($done) {
            $completed[] = ['key' => $key, 'name' => $quest['name'], 'coins' => $quest['coins'], 'xp' => $quest['xp']];
            $coins += $quest['coins'];
            $xp += $quest['xp'];
        }
    }

    if ($coins > 0 || $xp > 0) {
        $pdo->prepare('UPDATE users SET coins = coins + :c, xp = xp + :x WHERE id = :id')
            ->execute(['c' => $coins, 'x' => $xp, 'id' => $userId]);
    }

    return $completed;
}

/**
 * Première visite du jour : met à jour la série de connexion et crédite
 * la récompense journalière. Retourne null si déjà passé aujourd'hui.
 */
function ph_touch_daily(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT last_login_date, current_streak, best_streak FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $today = date('Y-m-d');
    if ($row['last_login_date'] === $today) {
        return null;
    }

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak = $row['last_login_date'] === $yesterday ? (int) $row['current_streak'] + 1 : 1;
    $bestStreak = max((int) $row['best_streak'], $streak);
    $coins = min(PH_DAILY_REWARD_BASE + PH_DAILY_REWARD_STEP * ($streak - 1), PH_DAILY_REWARD_MAX);

    $pdo->prepare(
        'UPDATE users SET last_login_date = :d, current_streak = :s, best_streak = :b, coins = coins + :c
         WHERE id = :id'
    )->execute(['d' => $today, 's' => $streak, 'b' => $bestStreak, 'c' => $coins, 'id' => $userId]);

    $userRow = ph_user_row($pdo, $userId);
    $achievements = $userRow === null ? [] : ph_grant_achievements($pdo, $userId, $userRow);

    $reward = ['streak' => $streak, 'coins' => $coins, 'achievements' => $achievements];
    // Mémorisée en session pour que l'accueil affiche la bannière même si
    // la récompense a été créditée pendant la connexion ou une partie.
    $_SESSION['daily_reward'] = ['streak' => $streak, 'coins' => $coins];

    return $reward;
}

/** Récupère (et consomme) la dernière récompense journalière à afficher. */
function ph_pull_daily_reward(): ?array
{
    if (!isset($_SESSION['daily_reward'])) {
        return null;
    }

    $reward = $_SESSION['daily_reward'];
    unset($_SESSION['daily_reward']);
    return $reward;
}

function ph_user_row(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Validation serveur : rejette les scores impossibles au vu des règles
 * du jeu (points par ennemi, quotas de vagues, durée de la partie).
 * Retourne un message d'erreur, ou null si la partie est plausible.
 */
function ph_validate_run(array $run): ?string
{
    $wave = $run['wave'];
    $kills = $run['kills'];

    if ($wave > 200) {
        return 'Vague invraisemblable.';
    }

    if ($run['accuracy'] > 100) {
        return 'Précision invalide.';
    }

    if ($run['combo'] > 99) {
        return 'Combo invalide.';
    }

    if ($run['headshots'] > $kills) {
        return 'Plus de headshots que d\'éliminations.';
    }

    if ($run['boss_kills'] > intdiv($wave, 5)) {
        return 'Trop de boss abattus pour cette vague.';
    }

    if ($run['challenges_done'] > max(0, $wave - intdiv($wave, 5))) {
        return 'Trop de défis réussis pour cette vague.';
    }

    // Quota d'apparitions : 6 + 2w par vague, marge pour les splitters.
    $maxKills = (int) (1.5 * ($wave * $wave + 7 * $wave)) + 30;
    if ($kills > $maxKills) {
        return 'Trop d\'éliminations pour cette vague.';
    }

    // Pire cas par élimination (boss + headshot + multiplicateurs + combo max)
    // plus bonus de vague (400w) et de défi (500w), avec une marge.
    $maxScore = $kills * 25000 + 450 * $wave * ($wave + 1) + intdiv($wave, 5) * 2500 + 5000;
    if ($run['score'] > $maxScore || $run['score'] > 50000000) {
        return 'Score impossible pour cette partie.';
    }

    $duration = $run['duration_seconds'];
    if ($duration > 0) {
        if ($duration < $wave * 3) {
            return 'Partie trop courte pour cette vague.';
        }
        if ($run['score'] > $duration * 20000) {
            return 'Score trop élevé pour la durée de la partie.';
        }
    }

    return null;
}

/**
 * Applique les résultats d'une partie déjà validée : statistiques,
 * XP, niveau, pièces, succès et quêtes. À appeler dans une transaction.
 */
function ph_apply_game_results(PDO $pdo, int $userId, array $run): array
{
    $xpGained = ph_xp_for_run($run);
    $coinsGained = ph_coins_for_run($run);

    $before = ph_user_row($pdo, $userId);
    $levelBefore = $before === null ? 1 : (int) $before['level'];

    $pdo->prepare(
        'UPDATE users SET
            xp = xp + :xp,
            coins = coins + :coins,
            games_played = games_played + 1,
            playtime_seconds = playtime_seconds + :duration,
            total_kills = total_kills + :kills,
            total_headshots = total_headshots + :headshots,
            total_boss_kills = total_boss_kills + :boss_kills
         WHERE id = :id'
    )->execute([
        'xp' => $xpGained,
        'coins' => $coinsGained,
        'duration' => $run['duration_seconds'],
        'kills' => $run['kills'],
        'headshots' => $run['headshots'],
        'boss_kills' => $run['boss_kills'],
        'id' => $userId,
    ]);

    $row = ph_user_row($pdo, $userId);
    $level = ph_level_for_xp((int) $row['xp']);
    if ($level !== (int) $row['level']) {
        $pdo->prepare('UPDATE users SET level = :l WHERE id = :id')->execute(['l' => $level, 'id' => $userId]);
        $row['level'] = $level;
    }

    $run['played'] = true;
    $achievements = ph_grant_achievements($pdo, $userId, $row, $run);
    $quests = ph_update_quests($pdo, $userId, $run);

    if ($quests !== []) {
        $row = ph_user_row($pdo, $userId);
        $afterQuests = ph_level_for_xp((int) $row['xp']);
        if ($afterQuests !== (int) $row['level']) {
            $pdo->prepare('UPDATE users SET level = :l WHERE id = :id')
                ->execute(['l' => $afterQuests, 'id' => $userId]);
        }
        $level = max($level, $afterQuests);
    }

    return [
        'xp' => $xpGained + array_sum(array_column($quests, 'xp')),
        'coins' => $coinsGained + array_sum(array_column($achievements, 'coins')) + array_sum(array_column($quests, 'coins')),
        'level' => $level,
        'levelUp' => $level > $levelBefore,
        'achievements' => $achievements,
        'quests' => $quests,
    ];
}
