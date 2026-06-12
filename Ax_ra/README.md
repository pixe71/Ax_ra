# Ax_ra — Pursuit Hunter

Portail de jeu PHP/MySQL autour de **Pursuit Hunter**, un shooter de vagues en canvas HTML5 :
comptes joueurs, scores serveur, progression, succès, quêtes journalières et classements.

## Fonctionnalités

- **Comptes** : inscription/connexion (hachage Argon2id, anti-CSRF, rate limiting).
- **Progression** : XP et niveaux de compte, pièces gagnées à chaque partie, séries de
  connexion quotidiennes avec récompenses croissantes.
- **Succès** : 17 badges à débloquer (dont des succès secrets), titres affichés sous le pseudo.
- **Quêtes journalières** : 3 objectifs par jour, récompenses en pièces et XP.
- **Profil public** : `profile.php?u=pseudo` — statistiques, historique des parties, badges,
  lien partageable.
- **Leaderboard** : filtres Global / Cette semaine / Autour de moi.
- **Mode entraînement** : `game.php?practice=1`, les scores ne sont pas enregistrés.
- **Anti-triche** : validation serveur de la plausibilité des scores (quotas de vagues,
  points par élimination, durée de partie).

La feuille de route complète (100 fonctionnalités) est dans [ROADMAP.md](ROADMAP.md).

## Installation

### Avec Docker

```bash
docker build -t ax_ra .
docker run -p 8080:80 \
  -e DB_HOST=<hôte MySQL> -e DB_NAME=pursuit_hunter \
  -e DB_USER=<utilisateur> -e DB_PASSWORD=<mot de passe> \
  ax_ra
```

### En local

1. Servir le dossier avec PHP ≥ 8.2 (`php -S 127.0.0.1:8080`) ou Apache/XAMPP.
2. Disposer d'un MySQL accessible ; configurer `DB_HOST`, `DB_PORT`, `DB_NAME`,
   `DB_USER`, `DB_PASSWORD` en variables d'environnement.
3. Le schéma est créé/migré automatiquement au premier chargement
   (`schema.sql` reste disponible pour une création manuelle).

## Architecture

| Fichier | Rôle |
|---|---|
| `config.php` | Connexion PDO, migrations de schéma, session, CSRF, rate limiting |
| `progression.php` | XP/niveaux, pièces, succès, titres, quêtes, séries de connexion |
| `auth.php` | Inscription / connexion |
| `index.php` | Accueil : compte, quêtes du jour, top 10 |
| `game.php` | Sert le jeu et lui injecte le jeton CSRF |
| `submit_score.php` | API JSON : validation anti-triche + enregistrement + récompenses |
| `leaderboard.php` | Classements filtrables |
| `profile.php` | Profil public et personnalisation du titre |
| `pursuit_hunter_v5.html` | Le jeu (canvas) |
