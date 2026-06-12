# Roadmap — Pursuit Hunter / Ax_ra

Feuille de route des 100 fonctionnalités envisagées pour transformer le site en portail de jeux complet.

**Légende** : ✅ implémenté · 🔶 partiellement implémenté · ⬜ à faire

> **Phase 1 (livrée)** : fondations de progression (XP, niveaux, pièces, succès, titres, quêtes,
> séries de connexion, profil public), filtres de leaderboard, mode entraînement et
> durcissement sécurité (Argon2id, CSRF, rate limiting, validation serveur des scores).

---

## 🧬 1. Progression & Profil (1-10)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 1 | Niveaux de compte globaux basés sur l'XP | ✅ | XP gagnée à chaque partie (`progression.php`), courbe 75·n·(n-1), barre d'XP sur l'accueil et le profil. |
| 2 | Badges de succès par jeu | ✅ | 17 succès avec récompenses en pièces, affichés sur le profil. |
| 3 | Titres personnalisables sous le pseudo | ✅ | Débloqués par les succès, sélection sur son profil, visibles sur les classements. |
| 4 | Historique détaillé des parties sur le profil | ✅ | 10 dernières parties : score, vague, précision, combo, durée. |
| 5 | Statistiques globales (temps de jeu, jeu le plus joué) | 🔶 | Temps de jeu total, parties, éliminations, précision moyenne. « Jeu le plus joué » prendra son sens avec un 2ᵉ jeu. |
| 6 | Ratio Victoires/Défaites (Winrate) | ⬜ | Sans objet pour un jeu de survie solo ; à prévoir avec le premier jeu PvP. |
| 7 | Niveaux de « Maîtrise » par jeu | ⬜ | Pertinent dès qu'il y aura plusieurs jeux ; la table `scores` par jeu est prête à être généralisée (colonne `game_id`). |
| 8 | Épinglage de jeux favoris | ⬜ | Nécessite un catalogue multi-jeux. |
| 9 | Séries (Streaks) de connexion quotidienne | ✅ | Série courante + record, succès « Fidèle au poste » à 7 jours. |
| 10 | Profil public partageable | ✅ | `profile.php?u=pseudo`, lien copiable depuis son profil. |

## 💬 2. Social & Communauté (11-20)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 11 | Amis avec confirmation | ⬜ | Table `friendships (user_id, friend_id, status)` ; prérequis du filtre « Amis » du leaderboard. |
| 12 | Chat global sur l'accueil | ⬜ | Simple en polling AJAX ; idéal avec WebSockets (voir #57). |
| 13 | Messagerie privée | ⬜ | Après #11. |
| 14 | Statut temps réel (En ligne, En jeu, Absent) | ⬜ | Colonne `last_seen_at` + heartbeat. |
| 15 | Guildes/clans avec tag | ⬜ | Tables `guilds`, `guild_members`. |
| 16 | Chat de guilde | ⬜ | Après #15. |
| 17 | Inviter un ami en session | ⬜ | Nécessite multijoueur (#57). |
| 18 | Défis asynchrones (« Battez le score de X ») | ⬜ | Faisable dès maintenant : notification + lien de défi. |
| 19 | Modération et signalement | ⬜ | Table `reports` + page admin. |
| 20 | Intégration Discord (Rich Presence) | ⬜ | Service externe ; nécessite une app Discord. |

## 🎨 3. Personnalisation & Cosmétiques (21-30)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 21 | Créateur d'avatars à débloquer | ⬜ | L'avatar actuel est l'initiale du pseudo ; les pièces (#31) serviront de monnaie. |
| 22 | Bannières de profil animées | ⬜ | |
| 23 | Couleur principale de l'interface au choix | ⬜ | Les pages utilisent déjà des variables CSS, le gros du travail est fait. |
| 24 | Mode sombre / clair | ⬜ | Idem #23 : basculer les variables CSS + `localStorage`. |
| 25 | Curseurs personnalisés par thème | ⬜ | |
| 26 | Bordures d'avatar selon le rang | ⬜ | Après les ligues (#43). |
| 27 | Feux d'artifice lors d'un record | ⬜ | Le serveur renvoie déjà `newBest: true` à `submit_score.php`, il n'y a plus qu'à animer côté jeu. |
| 28 | Effets sonores d'interface sélectionnables | ⬜ | |
| 29 | Musique de menu personnalisable | ⬜ | |
| 30 | Thèmes saisonniers automatiques | ⬜ | |

## 💰 4. Économie Virtuelle (31-40)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 31 | Monnaie gratuite (« Coins ») en fin de partie | ✅ | 10 + score/250 + 25/boss, créditée par `submit_score.php`. |
| 32 | Monnaie premium (« Gems ») | ⬜ | À coupler avec la monétisation (cat. 10). |
| 33 | Boutique de cosmétiques | ⬜ | Premier débouché pour les pièces déjà en place. |
| 34 | Récompenses de connexion journalière croissantes | ✅ | 10 → 60 pièces selon la série de connexion. |
| 35 | Coffres gratuits au passage de niveau | ⬜ | Le serveur détecte déjà les montées de niveau (`levelUp`). |
| 36 | Bourse d'échange entre joueurs | ⬜ | Après #33. |
| 37 | Cadeaux virtuels entre amis | ⬜ | Après #11 et #33. |
| 38 | Paris sur ses propres parties | ⬜ | Faisable : miser des pièces avant une partie contre son record. |
| 39 | Week-ends Double XP / Double Coins | ⬜ | Simple multiplicateur dans `progression.php` selon le jour. |
| 40 | Marchand mystère temporaire | ⬜ | Après #33. |

## 🏆 5. Compétition & Classements (41-50)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 41 | Filtres du leaderboard : Global, Amis, Autour de moi | 🔶 | Onglets Global / Cette semaine / Autour de moi livrés ; « Amis » attend le système d'amis (#11). |
| 42 | Saisons classées (reset trimestriel) | ⬜ | L'index sur `scores.created_at` permet déjà des classements par période. |
| 43 | Ligues (Bronze → Diamant) | ⬜ | |
| 44 | Tournois automatiques avec bracket | ⬜ | |
| 45 | Hall of Fame des saisons passées | ⬜ | Après #42. |
| 46 | Mode « Fantôme » contre le meilleur joueur | ⬜ | Nécessite l'enregistrement des replays côté jeu. |
| 47 | Notification quand ton score est battu | ⬜ | Détectable côté serveur à chaque soumission. |
| 48 | Matchmaking Elo/MMR | ⬜ | Nécessite le multijoueur (#57). |
| 49 | Pénalités d'abandon en multijoueur | ⬜ | Idem. |
| 50 | Classement inter-guildes | ⬜ | Après #15. |

## 🎮 6. Gameplay & Hub (51-60)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 51 | Accueil avec carrousel et recommandations | ⬜ | Pertinent à partir de 2-3 jeux. |
| 52 | Catégories de jeux | ⬜ | Idem. |
| 53 | Recherche de jeux avec auto-complétion | ⬜ | Idem. |
| 54 | Mini-jeu pendant les chargements | ⬜ | |
| 55 | Métajeu Clicker/Idle sur l'accueil | ⬜ | |
| 56 | Bouton « Jeu aléatoire » | ⬜ | À partir de 2 jeux. |
| 57 | WebSockets (Node.js) pour du temps réel | ⬜ | Gros chantier d'infrastructure : service dédié + reverse proxy. |
| 58 | Mode « Entraînement » sans enregistrement de score | ✅ | `game.php?practice=1`, le serveur n'écrit rien en base. |
| 59 | Tutoriel interactif au premier lancement | ⬜ | |
| 60 | Support manette (API Gamepad) | ⬜ | |

## 🎯 7. Rétention & Événements (61-70)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 61 | Quêtes journalières | ✅ | 3 quêtes/jour tirées d'un pool (déterministe par date), récompenses pièces + XP. |
| 62 | Quêtes hebdomadaires difficiles | ⬜ | Même moteur que #61 avec une période hebdomadaire. |
| 63 | Événements mondiaux collaboratifs | ⬜ | |
| 64 | Battle Pass gratuit/premium 50 paliers | ⬜ | S'appuiera sur l'XP déjà en place. |
| 65 | « Happy Hour » bonus 1 h | ⬜ | Simple multiplicateur horaire dans `progression.php`. |
| 66 | « Jeu de la semaine » récompenses doublées | ⬜ | À partir de 2 jeux. |
| 67 | Roue de la fortune quotidienne | ⬜ | |
| 68 | Succès cachés | ✅ | 2 succès secrets (« Sans bavure », « Oiseau de nuit ») affichés en ❓ tant que verrouillés. |
| 69 | Parrainage de nouveaux joueurs | ⬜ | |
| 70 | Notifications push web | ⬜ | Service worker + API Push (lié à la PWA #71). |

## ⚙️ 8. Qualité de Vie & Accessibilité (71-80)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 71 | PWA installable | ⬜ | `manifest.json` + service worker. |
| 72 | Interface 100 % responsive | 🔶 | Les pages ont des media queries ; à auditer systématiquement sur mobile. |
| 73 | Touches remappables sauvegardées | ⬜ | |
| 74 | Volumes Musique/Effets mémorisés | ⬜ | Le jeu n'a qu'un bouton muet ; à étendre avec `localStorage`. |
| 75 | Mode daltonien | ⬜ | |
| 76 | Bouton pause universel | 🔶 | Le jeu a déjà pause (P/Échap). |
| 77 | Sauvegarde auto des parties longues | ⬜ | |
| 78 | Lazy loading des images | ⬜ | Peu d'images pour l'instant. |
| 79 | Tooltips d'aide | ⬜ | |
| 80 | Page FAQ / support | ⬜ | |

## 🛡️ 9. Technique, Serveur & Sécurité (81-90)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 81 | Hachage Argon2 + renforcement de `auth.php` | ✅ | Argon2id (avec re-hash transparent à la connexion), mot de passe ≥ 8 caractères à l'inscription, `session_regenerate_id`, cookies `HttpOnly`/`SameSite`, plus de fuite du message d'exception. |
| 82 | Tokens anti-CSRF sur `submit_score.php` | ✅ | Jeton de session vérifié en en-tête `X-CSRF-Token` (jeu) et en champ caché (formulaires). |
| 83 | Rate limiting | ✅ | Limiteur en base : connexion 10/5 min, inscription 5/h, scores 30/10 min. |
| 84 | Validation serveur de plausibilité des scores | ✅ | Bornes dérivées des règles du jeu : quotas de vague, points max/élimination, ratio headshots, durée minimale, points/seconde. |
| 85 | Chiffrement des payloads de score | ⬜ | Apport limité (la clé serait visible côté client) ; la validation #84 + HTTPS couvrent l'essentiel. |
| 86 | Index SQL pour un leaderboard rapide | ✅ | Index existants conservés + `idx_scores_created_at` (classement hebdo) + clés composées sur les nouvelles tables. |
| 87 | Cache des classements (Redis) | ⬜ | Pertinent à fort trafic ; prévoir d'abord un cache fichier simple. |
| 88 | CI/CD GitHub | 🔶 | Lint PHP ajouté (`php-ci.yml`) en plus des workflows Docker existants ; tests automatisés à venir. |
| 89 | Dockerfile multi-stage | ⬜ | L'image actuelle est déjà simple ; à optimiser avec l'arrivée d'assets buildés. |
| 90 | Backups automatiques de la base | ⬜ | `mysqldump` planifié + rotation. |

## 💸 10. Monétisation (91-100)

| # | Fonctionnalité | Statut | Notes |
|---|---|---|---|
| 91 | Grade VIP mensuel | ⬜ | Toute la catégorie dépend d'un prestataire de paiement (Stripe…) et de mentions légales (CGV, TVA). |
| 92 | Bouton de don | ⬜ | Le plus simple pour commencer (lien Ko-fi/PayPal). |
| 93 | Publicités vidéo à récompense | ⬜ | |
| 94 | Bannières discrètes désactivables via VIP | ⬜ | |
| 95 | Accès anticipé aux nouveaux jeux | ⬜ | |
| 96 | Serveurs privés | ⬜ | Après le multijoueur (#57). |
| 97 | Extension de liste d'amis/guilde payante | ⬜ | |
| 98 | Boosts d'XP 24 h | ⬜ | Le moteur d'XP est prêt à recevoir un multiplicateur. |
| 99 | Packs « Fondateur » limités | ⬜ | |
| 100 | Codes promo partenaires | ⬜ | Table `promo_codes` + formulaire d'échange. |

---

## Phases suivantes suggérées

1. **Phase 2 — Social** : amis (#11) → filtre « Amis » du leaderboard (#41), défis asynchrones (#18), notification de record battu (#47).
2. **Phase 3 — Économie** : boutique de cosmétiques (#33) pour donner un débouché aux pièces, coffres de niveau (#35), week-ends bonus (#39).
3. **Phase 4 — Plateforme** : généralisation multi-jeux (`game_id` dans `scores`, catalogue, maîtrise par jeu #7), PWA (#71), mode clair (#24).
4. **Phase 5 — Temps réel** : WebSockets (#57), chat (#12), puis matchmaking (#48).
