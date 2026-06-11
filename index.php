<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = ph_db();
$user = ph_user();
$flash = ph_pull_flash();

$topScores = $pdo->query(
    'SELECT u.username, u.best_score, u.created_at
     FROM users u
     WHERE u.best_score > 0
     ORDER BY u.best_score DESC, u.created_at ASC
     LIMIT 10'
)->fetchAll();

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalScores = (int) $pdo->query('SELECT COUNT(*) FROM scores')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pursuit Hunter - Accueil</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #070b12;
            --panel: rgba(12, 18, 31, 0.82);
            --panel-strong: rgba(16, 24, 40, 0.96);
            --line: rgba(125, 255, 178, 0.18);
            --accent: #7dffb2;
            --accent-2: #66e0ff;
            --warn: #ffd54d;
            --text: #edf2ff;
            --muted: #9bb0cc;
            --danger: #ff6b6b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top, rgba(102, 224, 255, 0.16), transparent 40%),
                radial-gradient(circle at right top, rgba(125, 255, 178, 0.14), transparent 30%),
                linear-gradient(180deg, #0b1220 0%, #05070c 100%);
            color: var(--text);
        }

        .shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 40px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            border: 1px solid var(--line);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.96), rgba(9, 12, 20, 0.88));
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(16px);
        }

        .brand {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .brand .eyebrow {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--accent-2);
        }

        .brand h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 48px);
            letter-spacing: 2px;
        }

        .brand p {
            margin: 0;
            color: var(--muted);
            max-width: 720px;
            line-height: 1.6;
        }

        .cta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 13px 18px;
            text-decoration: none;
            font-weight: 800;
            letter-spacing: 1px;
            color: #fff;
            cursor: pointer;
        }

        .btn.primary {
            background: linear-gradient(135deg, #ff5b5b, #cc0000);
            box-shadow: 0 14px 30px rgba(255, 61, 61, 0.28);
        }

        .btn.secondary {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .grid {
            margin-top: 22px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 20px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 22px;
            background: var(--panel);
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 42px rgba(0, 0, 0, 0.28);
            overflow: hidden;
        }

        .panel .head {
            padding: 18px 20px 0;
        }

        .panel h2 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .panel .sub {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .content {
            padding: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .stat {
            padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .stat .n {
            font-size: 28px;
            font-weight: 900;
            color: var(--accent);
        }

        .stat .l {
            margin-top: 4px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ff9090;
        }

        .auth-grid {
            display: grid;
            gap: 14px;
        }

        form.card {
            padding: 18px;
            border-radius: 18px;
            background: var(--panel-strong);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .card h3 {
            margin: 0 0 12px;
            letter-spacing: 1px;
        }

        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
        }

        label {
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ffb3b3;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            outline: none;
        }

        input:focus {
            border-color: rgba(102, 224, 255, 0.75);
            box-shadow: 0 0 0 3px rgba(102, 224, 255, 0.15);
        }

        .flash {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 700;
        }

        .flash.success { background: rgba(125, 255, 178, 0.12); color: #9dffbf; border: 1px solid rgba(125, 255, 178, 0.22); }
        .flash.error { background: rgba(255, 107, 107, 0.12); color: #ffb3b3; border: 1px solid rgba(255, 107, 107, 0.22); }

        .leaderboard {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .entry {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .entry strong {
            display: block;
            font-size: 16px;
        }

        .entry span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .score {
            font-size: 22px;
            font-weight: 900;
            color: var(--warn);
        }

        .session {
            margin-top: 14px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(102, 224, 255, 0.08);
            border: 1px solid rgba(102, 224, 255, 0.18);
        }

        .session p {
            margin: 0 0 12px;
            color: var(--muted);
        }

        .footer {
            margin-top: 18px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 960px) {
            .grid { grid-template-columns: 1fr; }
            header { flex-direction: column; align-items: flex-start; }
            .cta { justify-content: flex-start; }
        }

        @media (max-width: 640px) {
            .shell { width: min(100% - 18px, 1180px); padding-top: 10px; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header>
            <div class="brand">
                <div class="eyebrow">Pursuit Hunter</div>
                <h1>Accueil</h1>
                <p>Connexion, inscription et classement des meilleurs scores. Lance ensuite la mission dans l'arène PHP.</p>
            </div>
            <div class="cta">
                <a class="btn primary" href="game.php">Jouer</a>
                <a class="btn secondary" href="leaderboard.php">Leaderboard</a>
                <?php if ($user): ?>
                    <a class="btn secondary" href="logout.php">Déconnexion</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?php echo ph_h($flash['type']); ?>"><?php echo ph_h($flash['message']); ?></div>
        <?php endif; ?>

        <section class="grid">
            <div class="panel">
                <div class="head">
                    <h2>Présentation</h2>
                    <p class="sub">Le site combine un accueil, un système de compte et un leaderboard serveur. Les scores envoyés depuis le jeu sont stockés en base de données.</p>
                </div>
                <div class="content">
                    <div class="stats">
                        <div class="stat">
                            <div class="n"><?php echo number_format($totalUsers, 0, ',', ' '); ?></div>
                            <div class="l">Comptes</div>
                        </div>
                        <div class="stat">
                            <div class="n"><?php echo number_format($totalScores, 0, ',', ' '); ?></div>
                            <div class="l">Scores envoyés</div>
                        </div>
                        <div class="stat">
                            <div class="n">PHP</div>
                            <div class="l">Backend</div>
                        </div>
                    </div>

                    <div class="session">
                        <?php if ($user): ?>
                            <p>Tu es connecté en tant que <strong><?php echo ph_h($user['username']); ?></strong>.</p>
                            <a class="btn primary" href="game.php">Lancer la partie</a>
                        <?php else: ?>
                            <p>Connecte-toi ou crée un compte pour pouvoir enregistrer tes scores dans le leaderboard.</p>
                            <a class="btn primary" href="#auth">Créer un compte</a>
                        <?php endif; ?>
                    </div>

                    <div class="footer">
                        Le jeu reste accessible dans le navigateur, mais l'enregistrement du score et le classement passent maintenant par le serveur.
                    </div>
                </div>
            </div>

            <div class="panel" id="auth">
                <div class="head">
                    <h2>Compte</h2>
                    <p class="sub">Inscription et connexion simples par pseudo et mot de passe.</p>
                </div>
                <div class="content auth-grid">
                    <?php if (!$user): ?>
                        <form class="card" method="post" action="auth.php">
                            <input type="hidden" name="action" value="register">
                            <h3>Créer un compte</h3>
                            <div class="field">
                                <label for="register_username">Pseudo</label>
                                <input id="register_username" name="username" minlength="3" maxlength="30" required>
                            </div>
                            <div class="field">
                                <label for="register_password">Mot de passe</label>
                                <input id="register_password" name="password" type="password" minlength="4" required>
                            </div>
                            <button class="btn primary" type="submit">S'inscrire</button>
                        </form>

                        <form class="card" method="post" action="auth.php">
                            <input type="hidden" name="action" value="login">
                            <h3>Se connecter</h3>
                            <div class="field">
                                <label for="login_username">Pseudo</label>
                                <input id="login_username" name="username" required>
                            </div>
                            <div class="field">
                                <label for="login_password">Mot de passe</label>
                                <input id="login_password" name="password" type="password" required>
                            </div>
                            <button class="btn secondary" type="submit">Connexion</button>
                        </form>
                    <?php else: ?>
                        <div class="card">
                            <h3>Ton compte</h3>
                            <p>Tu peux maintenant lancer le jeu et envoyer ton score automatiquement au leaderboard.</p>
                            <a class="btn primary" href="game.php">Jouer maintenant</a>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <h3>Top 10</h3>
                        <div class="leaderboard">
                            <?php if ($topScores): ?>
                                <?php foreach ($topScores as $index => $entry): ?>
                                    <div class="entry">
                                        <div>
                                            <strong>#<?php echo $index + 1; ?> <?php echo ph_h($entry['username']); ?></strong>
                                            <span>Inscrit le <?php echo ph_h(date('d/m/Y', strtotime($entry['created_at']))); ?></span>
                                        </div>
                                        <div class="score"><?php echo number_format((int) $entry['best_score'], 0, ',', ' '); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="entry">
                                    <div>
                                        <strong>Aucun score pour le moment</strong>
                                        <span>Sois le premier à entrer dans le classement.</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
