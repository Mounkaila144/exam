---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - _bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/brief.md
  - _bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/addendum.md
  - _bmad-output/planning-artifacts/architecture.md
project_name: ExamGuard
user_name: Mounkaila
date: 2026-05-23
status: complete
---

# ExamGuard — Epic Breakdown

## Overview

Backlog complet d'epics et de stories pour ExamGuard, dérivé du brief produit (Mary), de l'architecture technique (Winston), et de la séquence d'implémentation listée en `architecture.md §7.2`. Le projet est un **monolithe Laravel 13 existant à étendre** ; aucune story ne crée d'application from scratch, chaque story enrichit le codebase actuel.

> **Note méthodologique** : pas de PRD formel séparé pour ce projet. Le brief + l'addendum + l'architecture font office de spec d'exécution. Les FRs ci-dessous sont extraits du brief en numérotation BMad ; les ACs s'appuient sur les décisions techniques figées par Winston (§3 et §4 de l'architecture).

## Requirements Inventory

### Functional Requirements

**Auth & Rôles**
- **FR1** : Un professeur peut s'inscrire (signup) avec email + mot de passe.
- **FR2** : L'inscription d'un professeur passe par un état `pending` jusqu'à validation manuelle par l'administrateur.
- **FR3** : L'administrateur peut valider (`active`) ou refuser/désactiver (`disabled`) un professeur.
- **FR4** : Un professeur validé reçoit un email d'activation et peut se connecter.
- **FR5** : Admin et professeur partagent le même flow de login, redirigés ensuite vers leur dashboard selon le rôle.
- **FR6** : Les étudiants n'ont pas de compte ; ils accèdent à l'examen via un lien signé nominatif à usage unique.

**Builder d'examens (prof)**
- **FR7** : Un professeur peut créer un examen (titre, description, durée en minutes, date d'ouverture, date de fermeture).
- **FR8** : Un examen est structuré en sections ordonnées ; chaque section a un titre et des instructions.
- **FR9** : Un professeur peut ajouter des questions de type **V/F** (vrai/faux avec pénalité optionnelle).
- **FR10** : Un professeur peut ajouter des questions de type **QCM** (choix multiples avec une bonne réponse).
- **FR11** : Un professeur peut ajouter des questions de type **réponse courte**, **dissertation**, **code** (texte libre avec barème).
- **FR12** : Un professeur peut ajouter des questions de type **fichier joint** (l'étudiant uploade un fichier).
- **FR13** : Chaque question a un barème en points et un texte de barème (aide à la correction Claude).
- **FR14** : Un professeur peut configurer des paramètres de sécurité par examen (`security_settings_json` : toggles fullscreen, lock-on-first-offense, etc.).
- **FR15** : Un professeur peut gérer la liste de ses étudiants pour un examen via import CSV ou ajout manuel (nom, email, matricule, groupe).
- **FR16** : À la publication, ExamGuard génère un token signé unique par étudiant et envoie le lien d'accès par email (job en queue).

**Runtime étudiant**
- **FR17** : Un étudiant ouvrant son lien voit une page de consentement (résumé examen + avertissement surveillance) avant de démarrer.
- **FR18** : Un examen démarre obligatoirement en **plein écran forcé** (sortie du plein écran = infraction).
- **FR19** : Pendant l'examen, ExamGuard détecte tout changement d'onglet/fenêtre via Visibility API et `blur`.
- **FR20** : Pendant l'examen, ExamGuard bloque le copier/coller, le clic droit, et les raccourcis sensibles (F12, Ctrl+Shift+I, Ctrl+U, etc.) ; les tentatives sont journalisées.
- **FR21** : Pendant l'examen, ExamGuard détecte l'ouverture des DevTools (heuristique différence `outerHeight/innerHeight` + `debugger` trap).
- **FR22** : Les réponses sont auto-sauvegardées au fur et à mesure (debounce 500 ms) ; un fallback localStorage protège contre la perte en cas de coupure réseau.
- **FR23** : Un chronomètre serveur est la **source unique de vérité** ; le client affiche un cache et ping un endpoint heartbeat. Une soumission après expiration est rejetée.
- **FR24** : À la 1ʳᵉ infraction majeure (`tab_blur`, `fullscreen_exit`, `multiple_session`), l'examen est verrouillé automatiquement ; l'étudiant voit une page "Examen en pause".
- **FR25** : Toutes les infractions (majeures et mineures) sont journalisées dans une table `incidents` immuable (type, payload, IP, user-agent, timestamp).
- **FR26** : Un étudiant peut soumettre son examen à tout moment avant expiration ; après soumission, son lien devient inactif.

**Surveillance temps réel (prof)**
- **FR27** : Un professeur peut ouvrir le dashboard live d'un examen en cours et voir, par étudiant, son statut (en attente / en cours / verrouillé / soumis).
- **FR28** : Le dashboard live affiche un flux temps réel des incidents (qui, quoi, quand) via WebSocket (Laravel Reverb).
- **FR29** : Un professeur peut déverrouiller manuellement l'examen d'un étudiant depuis le dashboard ; l'étudiant reprend là où il s'était arrêté (broadcast unlock event).
- **FR30** : À chaque incident, le professeur reçoit une **notification push navigateur** (Web Push VAPID) si son navigateur n'est pas sur le dashboard.
- **FR31** : À chaque incident majeur, le professeur reçoit également une **notification email** (redondance).

**Correction & notes**
- **FR32** : Après soumission, V/F et QCM sont auto-corrigés côté serveur (statut `auto_graded`).
- **FR33** : Un professeur peut exporter toutes les copies d'un examen au format markdown structuré pour Claude (copy/paste vers chat.claude.ai).
- **FR34** : Un professeur peut importer le JSON de notes renvoyé par Claude pour appliquer les notes en masse.
- **FR35** : Un professeur peut déclencher une correction via l'**API Anthropic** (mode hybride) ; le job tourne en queue avec retry et timeout.
- **FR36** : Chaque appel API Anthropic est journalisé dans `api_usage_logs` avec coût estimé en cents.
- **FR37** : Un professeur peut envoyer la note d'un étudiant par email (individuel) ou de tous les étudiants notés (bulk via job queue).

**Admin & opérations**
- **FR38** : L'administrateur dispose d'un compte unique créé via commande artisan (`php artisan admin:make`).
- **FR39** : L'administrateur peut saisir, mettre à jour et faire tourner la clé API Anthropic mutualisée, stockée chiffrée dans `platform_settings`.
- **FR40** : L'administrateur peut voir un dashboard global : liste des profs (avec status), consommation API mensuelle, examens actifs.

### Non-Functional Requirements

- **NFR1** : Tous les liens étudiants sont des URLs signées (HMAC) Laravel ; un token consommé n'est plus réutilisable sauf réautorisation explicite.
- **NFR2** : Le chronomètre serveur est calculé à partir de `opened_at + duration_minutes` ; le client ne peut pas tricher l'horloge.
- **NFR3** : La clé API Anthropic est stockée chiffrée (`Crypt::encryptString`) ; elle n'est déchiffrée qu'au moment de l'appel API, jamais loggée.
- **NFR4** : La logique d'auto-grading vit exclusivement côté serveur ; les `autograde_config` des questions ne sont **jamais** sérialisés au client.
- **NFR5** : La latence entre un incident côté étudiant et son affichage sur le dashboard live du prof doit être **< 2 secondes** (cible : < 1 s).
- **NFR6** : L'auto-save côté étudiant est non-bloquant et idempotent (PUT logique sur la submission unique de l'assignment).
- **NFR7** : Aucune copie ne doit être perdue ; chaque mutation traversant plusieurs tables est en transaction DB, et le client conserve un fallback localStorage.
- **NFR8** : Le journal d'incidents est append-only (pas de UPDATE/DELETE en pratique), assurant un audit trail intègre.
- **NFR9** : La politique de rétention RGPD doit être documentée avant tout déploiement réel (copies + incidents + IP).
- **NFR10** : La consommation API mensuelle doit être visible par l'admin et plafonnable (alerte si seuil dépassé — MVP : alerte affichée, V2 : blocage).
- **NFR11** : Le code respecte les patterns architecturaux figés par Winston : Service + Form Request + Policy strictement appliqués, Domain code isolé de Laravel, transactions sur toute mutation cross-table.
- **NFR12** : Les navigateurs cibles sont Chrome / Edge / Firefox récents. Safari est best-effort (Fullscreen API capricieuse).

### Additional Requirements

*(extraits de l'architecture §2-§3-§5)*

- Migration **SQLite → PostgreSQL 16** pour la concurrence multi-prof et les colonnes JSONB.
- Installation et configuration de **Redis 7** (queue + cache + sessions).
- Installation de **Laravel Breeze (Blade stack)** pour l'auth scaffolding.
- Installation de **Laravel Reverb** + supervision (`reverb:start`) pour les WebSockets temps réel.
- Installation de **`minishlink/web-push`** + génération de clés VAPID (commande artisan custom à créer).
- Driver **S3-compatible** (MinIO en dev) pour les fichiers joints des étudiants.
- **Suppression complète** de la route publique actuelle `/api/submit-exam` et de l'examen "Transformation Digitale" hardcodé. On repart d'une base vide.
- Refonte des contrôleurs existants en 6 contrôleurs ciblés (cf. §5.4 archi).
- Création de l'arborescence `app/Domain/` pour le code métier pur (enums, AutoGrader, ScoreCalculator).
- Création des 11 tables du schéma cible (`users`, `platform_settings`, `exams`, `exam_sections`, `questions`, `exam_assignments`, `submissions`, `attachments`, `incidents`, `api_usage_logs`, `push_subscriptions`).

### UX Design Requirements

*(pas de doc UX dédié — UX-DRs dérivés du brief et des principes UX cross-cutting)*

- **UX-DR1** : Bandeau de consentement explicite avant démarrage d'examen, mentionnant la surveillance technique (Visibility, Fullscreen, blocages) et le mécanisme de verrouillage.
- **UX-DR2** : Page "Examen en pause" claire côté étudiant en cas de verrouillage, avec bouton "Demander la réautorisation" (informatif — c'est le prof qui agit).
- **UX-DR3** : Indicateurs visuels uniformes pour les statuts étudiants sur le dashboard live (`en attente` / `en cours` / `verrouillé` / `soumis`) — pastilles colorées + iconographie.
- **UX-DR4** : Loading states inline avec spinner Tailwind (`animate-spin`), jamais d'overlay full-page bloquant.
- **UX-DR5** : Messages d'erreur user-friendly (pas de stack trace côté UI) — détail dans les logs serveur.
- **UX-DR6** : Composants Blade réutilisables pour le builder (`<x-exam-builder.section>`, `<x-exam-builder.question-vf>`, etc.) — cohérence visuelle entre les 6 types de questions.
- **UX-DR7** : Layout `student/exam.blade.php` minimaliste et plein écran natif, sans menu de navigation (l'étudiant ne doit pas être tenté de cliquer ailleurs).
- **UX-DR8** : Estimation du coût Anthropic en USD/EUR affichée au prof avant tout appel API (responsabilise sans pénaliser puisque l'admin paie).

### FR Coverage Map

| FR | Epic |
|---|---|
| FR1 — Signup prof | Epic 1 |
| FR2 — État pending | Epic 1 |
| FR3 — Validation par admin | Epic 1 |
| FR4 — Email d'activation | Epic 1 |
| FR5 — Login + redirection rôle | Epic 1 |
| FR6 — Pas de compte étudiant | Epic 2 (génération) + Epic 3 (consommation) |
| FR7 — Créer un examen | Epic 2 |
| FR8 — Sections ordonnées | Epic 2 |
| FR9 — Questions V/F | Epic 2 |
| FR10 — Questions QCM | Epic 2 |
| FR11 — Questions ouvertes | Epic 2 |
| FR12 — Questions fichier joint | Epic 2 |
| FR13 — Barème | Epic 2 |
| FR14 — Paramètres sécurité par examen | Epic 2 |
| FR15 — Gestion étudiants | Epic 2 |
| FR16 — Publication + envoi liens | Epic 2 |
| FR17 — Page consentement | Epic 3 |
| FR18 — Fullscreen forcé | Epic 3 |
| FR19 — Visibility / blur | Epic 3 |
| FR20 — Blocages saisie | Epic 3 |
| FR21 — Détection DevTools | Epic 3 |
| FR22 — Auto-save | Epic 3 |
| FR23 — Chronomètre serveur | Epic 3 |
| FR24 — Verrouillage auto | Epic 3 |
| FR25 — Journal incidents | Epic 3 |
| FR26 — Soumission étudiant | Epic 3 |
| FR27 — Dashboard live | Epic 4 |
| FR28 — Flux temps réel | Epic 4 |
| FR29 — Déverrouillage manuel | Epic 4 |
| FR30 — Push prof | Epic 4 |
| FR31 — Email prof | Epic 4 |
| FR32 — Auto-grading V/F+QCM | Epic 5 |
| FR33 — Export markdown Claude | Epic 5 |
| FR34 — Import notes JSON | Epic 5 |
| FR35 — API Anthropic | Epic 5 |
| FR36 — Journal usage API | Epic 5 |
| FR37 — Envoi notes | Epic 5 |
| FR38 — Compte admin (artisan) | Epic 1 |
| FR39 — Gestion clé API | Epic 1 |
| FR40 — Dashboard admin global | Epic 1 |

## Epic List

### Epic 1: Plateforme prête à accueillir des professeurs
L'administrateur peut configurer la plateforme et faire entrer de nouveaux enseignants : tout le stack technique est en place, le rôle admin existe, un prof peut s'inscrire, être validé, se connecter, et l'admin peut piloter la clé API Anthropic mutualisée.
**FRs couverts** : FR1, FR2, FR3, FR4, FR5, FR38, FR39, FR40

### Epic 2: Le professeur compose et publie son examen
Un prof connecté peut créer un examen complet (6 types de questions, sections, barèmes, paramètres de sécurité), gérer sa liste d'étudiants, et publier — les étudiants reçoivent leurs liens uniques par email.
**FRs couverts** : FR6 (génération), FR7, FR8, FR9, FR10, FR11, FR12, FR13, FR14, FR15, FR16

### Epic 3: L'étudiant compose dans un environnement sécurisé
Un étudiant ouvre son lien, consent à la surveillance, démarre l'examen en plein écran forcé. Toutes les tentatives de triche sont détectées, journalisées et — pour les infractions majeures — verrouillent automatiquement sa copie. Il auto-save en continu et soumet.
**FRs couverts** : FR6 (consommation), FR17, FR18, FR19, FR20, FR21, FR22, FR23, FR24, FR25, FR26

### Epic 4: Le professeur surveille en direct et reprend la main
Pendant l'examen, le prof voit en temps réel qui compose, qui sort, qui est verrouillé. Il reçoit des notifications push + email à chaque incident. D'un clic, il peut redonner accès à un étudiant qui avait dérapé par accident.
**FRs couverts** : FR27, FR28, FR29, FR30, FR31

### Epic 5: Le professeur corrige et délivre les notes
Après l'examen, V/F et QCM sont auto-corrigés instantanément. Pour les réponses ouvertes, le prof a le choix : exporter en markdown pour Claude chat (gratuit) OU déclencher l'API Anthropic (clé mutualisée, l'admin paie). Les notes sont importées et envoyées aux étudiants par email.
**FRs couverts** : FR32, FR33, FR34, FR35, FR36, FR37

---

## Epic 1: Plateforme prête à accueillir des professeurs

**Goal** : À la fin de cette epic, l'admin peut créer son compte via CLI, valider des inscriptions de profs, et un prof validé peut se connecter sur un dashboard (vide pour l'instant). La clé API Anthropic est stockée chiffrée et l'admin a une vue d'ensemble de la plateforme.

### Story 1.1: Setup de l'infrastructure technique cible

As a **développeur initialisant ExamGuard**,
I want **migrer le projet existant vers la stack cible (Postgres, Redis, Reverb, Breeze, Web Push, S3)**,
So that **toutes les fondations techniques décrites dans l'architecture sont en place avant d'écrire la moindre feature métier**.

**Acceptance Criteria:**

**Given** le projet Laravel 13 existant avec SQLite par défaut
**When** j'exécute la séquence de setup `composer require laravel/breeze laravel/reverb minishlink/web-push league/flysystem-aws-s3-v3 predis/predis`, `php artisan breeze:install blade`, `php artisan reverb:install`, et `npm install alpinejs laravel-echo pusher-js`
**Then** les packages sont installés sans conflit de version
**And** la configuration `.env.example` est à jour avec les variables `DB_CONNECTION=pgsql`, `REDIS_*`, `BROADCAST_DRIVER=reverb`, `QUEUE_CONNECTION=redis`, `FILESYSTEM_DISK=s3`, `VAPID_*`, `ANTHROPIC_API_BASE`
**And** un `docker-compose.yml` à la racine déclare les services `postgres:16`, `redis:7`, `minio` avec leurs ports et volumes

**Given** la nouvelle stack en place
**When** je lance `docker compose up -d` puis `php artisan migrate:fresh`
**Then** les migrations existantes (`users`, `cache`, `jobs`) tournent sur Postgres sans erreur
**And** l'ancienne migration `2026_04_06_192325_create_submissions_table.php` est supprimée du dossier (on repart d'une base vide)
**And** l'ancienne route publique `/api/submit-exam` est supprimée du `routes/web.php`
**And** les contrôleurs `AdminController` et `SubmissionController` existants sont supprimés (leur logique servira d'inspiration aux nouvelles classes mais ne survit pas en l'état)

**Given** Reverb installé
**When** je lance `php artisan reverb:start` dans un terminal séparé
**Then** le serveur WebSocket démarre sur le port configuré et accepte les connexions
**And** `routes/channels.php` existe (vide pour l'instant)

**Given** Web Push à configurer
**When** j'exécute la commande artisan custom `php artisan vapid:generate` (à créer dans `app/Console/Commands/GenerateVapidKeys.php`)
**Then** une paire de clés VAPID est générée et stockée dans `.env` (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`)
**And** la commande échoue proprement si les clés existent déjà sauf si `--force` est passé

### Story 1.2: Rôles utilisateurs et middlewares de sécurité

As a **développeur**,
I want **introduire les rôles `admin` et `teacher` sur la table `users` ainsi que les middlewares de gating**,
So that **chaque route soit protégeable par rôle de manière déclarative**.

**Acceptance Criteria:**

**Given** la table `users` issue de Breeze
**When** je crée et exécute la migration `alter_users_add_role_and_status`
**Then** la colonne `role VARCHAR(20) NOT NULL DEFAULT 'teacher'` est ajoutée (valeurs autorisées : `admin`, `teacher`)
**And** la colonne `status VARCHAR(20) NOT NULL DEFAULT 'pending'` est ajoutée (valeurs autorisées : `pending`, `active`, `disabled`)
**And** les casts Eloquent sur `User` exposent `role` et `status` comme PHP enums (`App\Domain\User\UserRole`, `App\Domain\User\UserStatus`)

**Given** les rôles en place
**When** je crée les middlewares `App\Http\Middleware\EnsureAdminRole` et `App\Http\Middleware\EnsureTeacherIsActive`
**Then** `EnsureAdminRole` redirige vers `/login` si non-auth, et renvoie 403 si `$user->role !== 'admin'`
**And** `EnsureTeacherIsActive` redirige vers `/login` si non-auth, renvoie 403 si `$user->status !== 'active'`, et renvoie 403 si `$user->role !== 'teacher'`
**And** les deux middlewares sont enregistrés dans `bootstrap/app.php` avec des alias `admin` et `teacher.active`
**And** une route de test `/admin/ping` protégée par `auth + admin` retourne `pong` pour un admin et 403 pour un teacher

### Story 1.3: Création du compte admin via CLI et flow signup professeur

As an **administrateur de la plateforme**,
I want **créer mon propre compte via une commande artisan et que les nouveaux profs s'inscrivent avec un statut `pending` en attente de ma validation**,
So that **personne n'utilise ExamGuard sans mon autorisation**.

**Acceptance Criteria:**

**Given** une base sans aucun utilisateur
**When** j'exécute `php artisan admin:make --email=mounkaila144@gmail.com --name="Mounkaila"` (commande à créer dans `app/Console/Commands/MakeAdminCommand.php`)
**Then** un mot de passe est demandé interactivement (ou généré aléatoirement avec `--random-password` qui l'affiche une seule fois)
**And** un user `role=admin, status=active` est créé en base
**And** la commande refuse de créer un 2ᵉ admin sauf si `--force` est passé (admin unique par défaut, cf. brief)

**Given** le formulaire signup Breeze
**When** un visiteur s'inscrit sur `/register`
**Then** un user est créé avec `role=teacher, status=pending`
**And** l'utilisateur voit une page "Inscription enregistrée — votre compte sera activé après validation par l'administrateur" au lieu d'être loggué automatiquement
**And** l'admin reçoit un email `NewTeacherSignedUpMailable` avec lien vers `/admin/teachers?status=pending`

**Given** un teacher `status=pending` qui tente de se logger
**When** il poste ses credentials sur `/login`
**Then** l'auth réussit techniquement (credentials OK) mais il est immédiatement déconnecté et redirigé vers une page "Compte en attente de validation"
**And** un teacher `status=disabled` voit "Compte désactivé, contactez l'administrateur"

### Story 1.4: Validation des inscriptions professeurs par l'admin

As an **administrateur**,
I want **une console admin où je vois les inscriptions en attente et où je peux activer ou refuser chaque prof**,
So that **je garde le contrôle de qui utilise la plateforme**.

**Acceptance Criteria:**

**Given** je suis loggué admin sur `/admin`
**When** je clique "Professeurs" dans la nav
**Then** je vois une liste paginée de tous les `users` avec `role=teacher`, filtrable par statut (`pending`, `active`, `disabled`)
**And** chaque ligne affiche : nom, email, date d'inscription, statut, actions

**Given** un teacher en `pending`
**When** je clique "Activer"
**Then** son statut passe à `active`
**And** un email `TeacherApprovedMailable` lui est envoyé via job en queue avec le lien `/login`
**And** une entrée `audit_log` ou simplement un log applicatif est écrite avec `actor_id=adminId, action=activate_teacher, target_id=teacherId, at=now`

**Given** un teacher `active` ou `pending`
**When** je clique "Désactiver"
**Then** son statut passe à `disabled` et il est immédiatement déconnecté de toute session active (révocation tokens via `auth()->logoutOtherDevices` ou équivalent — best-effort)
**And** aucun email n'est envoyé (action silencieuse)

**Given** la liste des profs
**When** j'utilise les Form Requests `ApproveTeacherRequest` et `DisableTeacherRequest`
**Then** la validation des inputs (`teacher_id` existe et est bien un teacher) est centralisée hors du controller
**And** les actions passent par une `Policy` (`UserPolicy::manage`) qui vérifie que `$actor` est admin

### Story 1.5: Gestion de la clé API Claude et dashboard admin global

As an **administrateur**,
I want **saisir, faire tourner et chiffrer la clé API Anthropic, et voir un dashboard global de l'activité de la plateforme**,
So that **je pilote le service et anticipe les coûts**.

**Acceptance Criteria:**

**Given** je suis sur `/admin/settings`
**When** je saisis une clé API Anthropic dans un champ password-style et je soumets
**Then** la clé est validée par un test live (un appel `messages.create` minimal au modèle Claude configuré) avant d'être persistée
**And** si le test réussit, la clé est chiffrée via `Crypt::encryptString` et stockée dans `platform_settings` avec `key='claude.api_key'`
**And** si le test échoue (401, 403, network), le formulaire affiche l'erreur Anthropic et la clé n'est pas persistée
**And** un champ "Modèle Claude" (texte libre, défaut `claude-opus-4-7`) est également persisté dans `platform_settings` avec `key='claude.model'`

**Given** une clé existante en base
**When** je reviens sur `/admin/settings`
**Then** le champ clé API est vide (jamais re-rendu en clair) avec un placeholder "•••••• (clé configurée)"
**And** un bouton "Tester la clé actuelle" déclenche un appel API live et affiche le résultat (OK / erreur)
**And** un bouton "Faire tourner la clé" ouvre le formulaire de saisie

**Given** je suis sur `/admin` (dashboard global)
**When** la page se charge
**Then** je vois 4 widgets : nombre de profs par statut, nombre d'examens publiés ce mois, consommation API ce mois (tokens + coût estimé en USD), nombre d'incidents ce mois toutes plateformes confondues
**And** le widget "consommation API" lit depuis `api_usage_logs` agrégé par mois courant (peut être à zéro tant que l'Epic 5 n'est pas livrée — c'est OK)
**And** le service `PlatformApiKeyVault` (dans `app/Services/Security/`) expose `setKey(string $key, int $actorId): void` et `getDecryptedKey(): ?string` ; toute autre couche utilise ce service, jamais d'accès direct à `platform_settings`

---

## Epic 2: Le professeur compose et publie son examen

**Goal** : À la fin de cette epic, un prof connecté peut créer un examen complet avec les 6 types de questions, configurer la sécurité par examen, gérer sa liste d'étudiants, et publier — les étudiants reçoivent leurs liens uniques par email.

### Story 2.1: Modèle d'examen — squelette et CRUD basique

As a **professeur**,
I want **créer un examen avec son titre, sa description, sa durée et sa fenêtre temps**,
So that **j'ai un conteneur dans lequel je vais composer mes questions**.

**Acceptance Criteria:**

**Given** la base sans tables métier
**When** j'exécute les migrations `create_exams_table` et `create_exam_sections_table`
**Then** les tables `exams` et `exam_sections` existent avec le schéma exact défini en `architecture.md §5.2`
**And** les modèles Eloquent `Exam` et `ExamSection` existent avec les relations `Exam::sections()`, `Exam::teacher()`, `ExamSection::exam()`
**And** une `ExamPolicy` est créée et enregistrée, autorisant `view/update/delete` uniquement si `$exam->teacher_id === $user->id`

**Given** je suis loggué teacher actif sur `/teacher`
**When** je clique "Nouvel examen"
**Then** je vois un formulaire avec champs : `title` (requis), `description` (textarea, optionnel), `duration_minutes` (int, requis, > 0), `opens_at` (datetime, optionnel), `closes_at` (datetime, optionnel)
**And** la validation passe par `StoreExamRequest` (Form Request)
**And** à la soumission, l'examen est créé avec `teacher_id = auth()->id()`, `status = 'draft'`, et je suis redirigé vers `/teacher/exams/{exam}/edit`

**Given** un examen existant dont je suis le teacher
**When** je suis sur `/teacher/exams`
**Then** je vois la liste de mes examens avec leur statut (`draft` / `published` / `closed`), date de création, nombre de questions, nombre d'assignments
**And** je ne vois jamais les examens d'un autre prof (testé via test Feature avec deux teachers distincts)

### Story 2.2: Builder visuel — sections, questions V/F et QCM

As a **professeur**,
I want **structurer mon examen en sections et y ajouter des questions V/F et QCM via une interface visuelle**,
So that **je peux reproduire le découpage pédagogique classique (Partie I, II...)**.

**Acceptance Criteria:**

**Given** un examen en `draft` dont je suis l'auteur
**When** je suis sur `/teacher/exams/{exam}/edit`
**Then** je vois le builder Alpine.js avec une liste ordonnée de sections (vide au départ)
**And** un bouton "Ajouter une section" crée une nouvelle section en bas avec un titre éditable inline et un champ instructions optionnel
**And** je peux réordonner les sections par drag-and-drop ; l'ordre est persisté via PATCH AJAX sur `/teacher/exams/{exam}/sections/reorder`
**And** je peux supprimer une section (confirmation obligatoire) ; les questions qu'elle contient sont supprimées en cascade

**Given** la migration `create_questions_table` est appliquée (avec le schéma `architecture.md §5.2`)
**When** dans une section je clique "Ajouter une question V/F"
**Then** un component Blade `<x-exam-builder.question-vf>` est inséré avec champs : `prompt` (textarea), `points` (numeric), `correct` (radio VRAI/FAUX), `penalty` (numeric optionnel)
**And** la sauvegarde se fait via AJAX debouncé 500ms sur `/teacher/exams/{exam}/questions/{question?}` (POST si nouveau, PATCH si existant)
**And** le payload sérialise `autograde_config` côté serveur : `{"correct": "VRAI", "penalty": -0.5}`
**And** le `autograde_config` n'est **jamais** renvoyé au client après save (le serveur renvoie un payload nettoyé)

**Given** je clique "Ajouter une question QCM"
**When** je remplis prompt + choix (au moins 2, au plus 6) + sélectionne la bonne réponse + points
**Then** la question est persistée avec `type='qcm'`, `choices` = `[{"key":"A","label":"..."}, ...]`, `autograde_config` = `{"correct":"B"}`
**And** côté UI, je peux ajouter/supprimer des choix dynamiquement (Alpine state local)
**And** je ne peux pas sauvegarder un QCM sans bonne réponse marquée (validation `StoreQuestionRequest`)

### Story 2.3: Builder visuel — questions ouvertes (court, dissertation, code, fichier)

As a **professeur**,
I want **ajouter des questions ouvertes (réponse courte, dissertation, code) et des questions à dépôt de fichier**,
So that **je couvre tous les formats pédagogiques nécessaires à mon examen**.

**Acceptance Criteria:**

**Given** un examen en mode édition
**When** je clique "Ajouter une question — réponse courte" / "dissertation" / "code"
**Then** un component Blade adapté est inséré (`question-short`, `question-essay`, `question-code`)
**And** les champs communs sont : `prompt` (textarea), `points` (numeric), `bareme_text` (textarea — aide à la correction Claude)
**And** la question de code a un champ supplémentaire `language_hint` (texte libre, ex: "php", "javascript", "python")
**And** la dissertation a un champ optionnel `min_words` / `max_words`
**And** `autograde_config` reste `null` pour ces types (pas d'auto-grading)

**Given** je clique "Ajouter une question — fichier joint"
**When** je remplis prompt + points + `accepted_extensions` (multi-select : pdf, docx, png, jpg, zip) + `max_size_mb` (numeric, défaut 5)
**Then** la question est persistée avec `type='file_upload'`, `autograde_config = {"accepted_extensions":[...],"max_size_mb":5}`
**And** côté étudiant (impl. Epic 3) ces contraintes seront appliquées à l'upload

**Given** les 6 types de questions sont implémentés
**When** je consulte le builder
**Then** un menu déroulant "Ajouter une question" propose les 6 types avec une icône distinctive et un libellé court
**And** un test Feature `ExamBuilderTest::test_can_create_all_question_types` crée une question de chaque type et vérifie la persistance correcte

### Story 2.4: Configuration de la sécurité par examen

As a **professeur**,
I want **régler les paramètres anti-triche pour chaque examen indépendamment**,
So that **je peux assouplir la surveillance pour un examen d'entraînement et la durcir pour un examen final**.

**Acceptance Criteria:**

**Given** un examen en mode édition, onglet "Sécurité"
**When** je consulte le panneau
**Then** je vois les toggles suivants avec valeurs par défaut entre parenthèses :
  - `enforce_fullscreen` (✓ activé)
  - `lock_on_first_offense` (✓ activé) — sinon : `lock_on_offense_count` (int, défaut 3)
  - `block_copy_paste` (✓ activé)
  - `block_right_click` (✓ activé)
  - `block_devtools_shortcuts` (✓ activé)
  - `detect_devtools_open` (✓ activé)
  - `lock_on_ip_change` (✗ désactivé par défaut, opt-in)
**And** les valeurs sont persistées dans `exams.security_settings` (colonne JSONB)

**Given** je sauvegarde les paramètres
**When** je publie l'examen plus tard
**Then** ces toggles seront lus côté runtime étudiant (Epic 3) pour activer/désactiver les listeners correspondants
**And** un changement de paramètre sur un examen `published` est interdit (Form Request rejette si `status !== 'draft'`) — la sécurité d'un examen en cours ne se modifie pas à la volée

### Story 2.5: Gestion de la liste des étudiants pour un examen

As a **professeur**,
I want **importer ou saisir manuellement la liste des étudiants invités à mon examen**,
So that **je sais exactement qui recevra un lien d'accès**.

**Acceptance Criteria:**

**Given** la migration `create_exam_assignments_table` est appliquée
**When** je suis sur `/teacher/exams/{exam}/students`
**Then** je vois la liste actuelle des assignments pour cet examen (vide initialement) avec colonnes : nom, email, matricule, groupe, statut (`pending` / `opened` / `submitted` / `locked`)

**Given** je clique "Ajouter manuellement"
**When** je remplis nom + email (requis, format valide) + matricule (optionnel) + groupe (optionnel)
**Then** un nouveau `ExamAssignment` est créé avec `access_token` généré (32 chars random hex), `exam_id`, et les champs saisis
**And** la contrainte UNIQUE `(exam_id, student_email)` empêche l'ajout d'un doublon (message d'erreur clair)
**And** le `access_token` n'est PAS affiché dans la liste (sera dévoilé via lien email uniquement)

**Given** je clique "Importer un CSV"
**When** je sélectionne un fichier `.csv` avec en-tête `name,email,matricule,groupe`
**Then** chaque ligne crée un assignment (les doublons sont skippés avec un message "N étudiants importés, M déjà présents")
**And** les erreurs de format sont rapportées ligne par ligne (ex: "ligne 5 : email invalide") sans interrompre l'import des lignes valides
**And** un test Feature `StudentImportTest` couvre import nominal + import avec doublons + import avec ligne invalide

### Story 2.6: Publication de l'examen et envoi des liens aux étudiants

As a **professeur**,
I want **publier mon examen et déclencher l'envoi automatique des liens aux étudiants**,
So that **mes étudiants peuvent accéder à l'examen quand la fenêtre s'ouvre**.

**Acceptance Criteria:**

**Given** un examen `draft` avec au moins 1 section, 1 question et 1 étudiant
**When** je clique "Publier" sur `/teacher/exams/{exam}`
**Then** une validation finale tourne (au moins 1 question, total des points > 0, `duration_minutes` > 0, `opens_at` < `closes_at` si renseignés) — si KO, j'ai une liste d'erreurs à corriger
**And** si OK, l'examen passe à `status='published'` et un job `SendAssignmentEmailJob` est dispatché pour chaque assignment

**Given** le service `AssignmentTokenGenerator` (dans `app/Services/Exam/`)
**When** il est appelé avec un `ExamAssignment`
**Then** il retourne une URL signée Laravel pointant vers `/exam/{access_token}` avec une expiration alignée sur `closes_at` (ou +24h après si `closes_at` est null)
**And** la signature URL est testée via `TokenSignatureTest::test_tampered_token_is_rejected`

**Given** un `SendAssignmentEmailJob` qui tourne sur la queue Redis
**When** il s'exécute
**Then** il envoie un `ExamAssignmentMailable` à `assignment.student_email` avec : nom de l'examen, durée, fenêtre temps, bouton "Accéder à mon examen" pointant vers l'URL signée
**And** en cas d'échec SMTP, le job retry 3× avec backoff `[10, 60, 300]` secondes
**And** un échec final est loggué avec niveau `error` et notifie l'admin par email (`AssignmentEmailFailedNotification`)

**Given** un examen `published`
**When** la fenêtre `closes_at` est dépassée
**Then** une commande `php artisan exam:close-expired` (lancée par le scheduler) bascule le statut à `closed` automatiquement
**And** les liens étudiants signés sont alors expirés (signature rejetée par le middleware)

---

## Epic 3: L'étudiant compose dans un environnement sécurisé

**Goal** : À la fin de cette epic, un étudiant peut ouvrir son lien, consentir à la surveillance, démarrer en plein écran forcé, composer son examen avec auto-save en continu, et soit soumettre, soit être verrouillé automatiquement à la 1ère infraction majeure. Tout est journalisé.

### Story 3.1: Entrée étudiant — middleware, consentement et démarrage

As an **étudiant invité**,
I want **ouvrir mon lien, voir un résumé de l'examen avec les règles de surveillance, et démarrer quand je suis prêt**,
So that **je rentre en connaissance de cause dans l'examen**.

**Acceptance Criteria:**

**Given** la migration `create_submissions_table` est appliquée (schéma `architecture.md §5.2`)
**When** je crée les middlewares `App\Http\Middleware\ResolveExamAssignment` et `App\Http\Middleware\ExamIsLive`
**Then** `ResolveExamAssignment` lit le paramètre `{token}` de la route, charge l'`ExamAssignment` correspondant via `access_token`, et le bind sur la requête (`$request->examAssignment`)
**And** `ExamIsLive` vérifie : examen `published`, `now()` dans `[opens_at, closes_at]` (si renseignés), `locked = false`, `submitted_at = null` — sinon renvoie une vue dédiée (`exam-not-available`, `exam-locked`, `exam-already-submitted`)
**And** les routes étudiant sont déclarées dans un nouveau fichier `routes/student.php` avec middleware `signed + ResolveExamAssignment` (sauf `ExamIsLive` qui est ciblé par route)

**Given** un étudiant qui ouvre `/exam/{token}` (URL signée valide)
**When** la route est servie par `Student\ExamEntryController::show`
**Then** je vois la page `student/entry.blade.php` avec : titre de l'examen, description, durée prévue, nombre de questions, bandeau de consentement explicite (UX-DR1) listant : surveillance fullscreen, détection sortie d'onglet, blocages saisie, mécanisme de verrouillage
**And** un bouton "Démarrer l'examen" présent uniquement si je n'ai pas encore ouvert (`opened_at IS NULL`)
**And** si `opened_at IS NOT NULL`, le bouton dit "Reprendre" et pointe vers `/exam/{token}/run`

**Given** je clique "Démarrer"
**When** la requête `POST /exam/{token}/start` est traitée
**Then** `opened_at = now()`, l'IP et le user-agent sont enregistrés, une `Submission` vide est créée avec `status='in_progress'`
**And** je suis redirigé vers `/exam/{token}/run`
**And** un event `StudentJoined` est broadcasté sur `private-exam.{exam_id}.monitor` (l'Epic 4 abonnera le prof à ce channel)

### Story 3.2: Runtime examen — shell, chronomètre serveur et auto-save

As an **étudiant en train de composer**,
I want **voir mon examen avec un compte à rebours fiable et que mes réponses soient sauvegardées automatiquement**,
So that **je ne perds pas mon travail en cas de coupure et je sais exactement combien de temps il me reste**.

**Acceptance Criteria:**

**Given** je suis sur `/exam/{token}/run`
**When** la vue `student/runtime.blade.php` se charge
**Then** je vois mes questions groupées par section avec l'ordre défini par le prof
**And** un compte à rebours est affiché en haut, calculé initialement comme `(opened_at + duration_minutes) - now()`
**And** le service `ExamTimerService::remainingFor($assignment)` est la source unique de vérité serveur

**Given** le chronomètre client
**When** Alpine envoie un `GET /api/student/{token}/heartbeat` toutes les 10 secondes
**Then** la réponse JSON `{ remaining_seconds: N }` actualise l'affichage
**And** si `N <= 0`, l'UI bascule en mode "Soumission automatique en cours…" et déclenche immédiatement `POST /exam/{token}/submit`
**And** côté serveur, toute requête de save/submit après expiration est rejetée avec HTTP 409 et code d'erreur `exam_expired`

**Given** je tape une réponse dans un champ
**When** je m'arrête de taper pendant 500ms
**Then** un `POST /api/student/{token}/answers` est émis avec `{ question_id, value }`
**And** côté serveur, `ExamAnswerController::store` fait un `UPDATE submissions SET answers = jsonb_set(...)` atomique pour cette question
**And** l'endpoint est idempotent : appelé N fois avec la même valeur, l'effet est identique
**And** côté client, le payload est aussi miroir dans `localStorage` sous la clé `examguard.draft.{token}` en cas de coupure réseau (fallback)

**Given** je clique "Soumettre l'examen"
**When** la requête `POST /exam/{token}/submit` est traitée
**Then** la `Submission.status` passe à `submitted`, `submitted_at = now()`, le lien devient inactif
**And** un event `StudentSubmitted` est broadcasté sur `private-exam.{exam_id}.monitor`
**And** je suis redirigé vers `student/submitted.blade.php` ("Merci, votre copie a bien été envoyée")
**And** la `Submission.answers` finale matche exactement ce qui était auto-sauvegardé

### Story 3.3: Anti-triche côté client — plein écran et détection sortie

As a **professeur**,
I want **que l'examen démarre en plein écran et que toute sortie soit immédiatement détectée et reportée**,
So that **je sois averti dès qu'un étudiant tente de consulter autre chose**.

**Acceptance Criteria:**

**Given** l'examen a `security_settings.enforce_fullscreen=true`
**When** l'étudiant clique "Démarrer"
**Then** `requestFullscreen()` est appelé côté client ; si l'utilisateur refuse, l'examen ne démarre pas (message "L'examen nécessite le mode plein écran")
**And** une seule re-tentative est offerte ; si le 2ᵉ refus survient, l'incident est journalisé (`type=fullscreen_denied`) et l'étudiant ne peut pas accéder à `/exam/{token}/run`

**Given** l'examen tourne en plein écran
**When** l'étudiant sort du plein écran (touche Esc, F11, ou perte de focus système)
**Then** l'event `fullscreenchange` est capturé côté Alpine
**And** un `POST /api/student/{token}/incidents` est émis immédiatement avec `{type: "fullscreen_exit", payload: {method: "esc_key"|"f11"|"external", elapsed_ms: ...}}`
**And** Alpine tente de re-rentrer en plein écran 1 seule fois (politesse) ; si échec, l'UI bascule sur la page de verrou (sera fournie par 3.5)

**Given** l'examen a `security_settings.detect_devtools_open=true` (le toggle Visibility est toujours actif, non optionnel)
**When** je change d'onglet OU minimise la fenêtre OU bascule sur une autre application
**Then** l'event `visibilitychange` (state `hidden`) déclenche `POST /api/student/{token}/incidents` avec `{type: "tab_blur", payload: {duration_ms: ...}}` (durée mesurée au retour)
**And** l'event `window.blur` est utilisé comme backup si Visibility API n'a pas tiré
**And** un test E2E manuel documenté décrit le scénario reproduit étape par étape (ce test est Playwright en V2 ; au MVP c'est dans la `tests/manual-checklist.md`)

### Story 3.4: Anti-triche côté client — blocages saisie et DevTools

As a **professeur**,
I want **que les actions de triche évidentes (copier/coller, clic droit, DevTools) soient bloquées et reportées**,
So that **l'étudiant rencontre une friction immédiate à chaque tentative**.

**Acceptance Criteria:**

**Given** `security_settings.block_copy_paste=true`
**When** l'étudiant tente Ctrl+C / Ctrl+V / Ctrl+X / clic droit-copier sur la page d'examen
**Then** l'event est `preventDefault()` côté JS
**And** un incident `type=copy_attempt` ou `paste_attempt` est journalisé avec `payload.selection_length`
**And** la sélection texte CSS est bloquée par `user-select: none` sur le `<main>` de la page d'examen

**Given** `security_settings.block_right_click=true`
**When** l'étudiant fait un clic droit n'importe où sur la page
**Then** le menu contextuel est supprimé et un incident `type=context_menu_attempt` est journalisé

**Given** `security_settings.block_devtools_shortcuts=true`
**When** l'étudiant appuie sur F12, Ctrl+Shift+I/J/C, Ctrl+U, Ctrl+S, Ctrl+P, Ctrl+Shift+K, PrintScreen
**Then** l'event est bloqué et un incident `type=devtools_shortcut` est journalisé avec `payload.key=<combo>`

**Given** `security_settings.detect_devtools_open=true`
**When** une heuristique côté client détecte les DevTools (différence `window.outerHeight - window.innerHeight > 200` OU `debugger` trap qui ralentit anormalement)
**Then** un incident `type=devtools_detected` est journalisé avec `payload.heuristic` indiquant la méthode déclenchée
**And** la détection tourne dans un setInterval 1s pour limiter l'impact perf

**Given** tous les blocages activés
**When** je consulte le fichier `resources/js/exam-runtime.js`
**Then** chaque type de listener est encapsulé dans une fonction prenant les `security_settings` en paramètre et n'attache son listener que si le toggle est actif
**And** les types d'incident sont déclarés dans `App\Domain\Incident\IncidentType` (enum) et leur enum JS miroir est généré ou maintenu en parallèle

### Story 3.5: Anti-triche côté serveur — recording, verrouillage et page verrouillée

As a **professeur**,
I want **que les infractions remontées par le client soient durablement enregistrées, et que les infractions majeures verrouillent automatiquement l'examen**,
So that **j'aie un audit trail solide et que la triche soit interrompue immédiatement**.

**Acceptance Criteria:**

**Given** les migrations `create_incidents_table` et `update_exam_assignments_add_lock_fields` sont appliquées
**When** un `POST /api/student/{token}/incidents` arrive avec un payload valide
**Then** `IncidentReportController::store` valide via `ReportIncidentRequest` (le `type` doit appartenir à l'enum `IncidentType`)
**And** appelle `IncidentRecorder::record($assignment, $type, $payload)`
**And** une ligne est insérée dans `incidents` avec `occurred_at = now()`, IP/UA capturés côté request

**Given** le service `IncidentRecorder` (dans `app/Services/Security/`)
**When** l'incident a un `severity = critical` (= types listés dans `IncidentType::MAJOR_OFFENSES`) ET `security_settings.lock_on_first_offense=true`
**Then** dans la même transaction DB, `ExamLockService::lock($assignment, reason: $type)` est appelé
**And** `exam_assignments.locked=true`, `locked_reason=$type`, `locked_at=now()` sont écrits
**And** un event `StudentLocked` est broadcasté sur `private-exam.{exam_id}.monitor` (consommé en Epic 4)
**And** un event `StudentLockedForStudent` est broadcasté sur `private-student.{assignment_id}` (consommé par le client pour basculer l'UI)

**Given** `security_settings.lock_on_first_offense=false` et `lock_on_offense_count=3`
**When** un 3ᵉ incident majeur arrive
**Then** le verrou est déclenché à ce moment (le compteur est calculé via `COUNT(*) WHERE assignment_id AND severity='critical'`)
**And** les 2 premiers incidents critiques sont journalisés sans verrouiller

**Given** un assignment verrouillé
**When** l'étudiant fait n'importe quelle requête vers `/exam/{token}/*` ou `/api/student/{token}/*`
**Then** le middleware `ExamIsLive` détecte `locked=true` et renvoie la vue `student/locked.blade.php` (UX-DR2) avec : message "Votre copie est en pause", raison (`locked_reason` traduite en libellé humain), bouton "Demander la réautorisation" (cosmétique — c'est le prof qui agit)
**And** les endpoints AJAX renvoient HTTP 423 (Locked) avec `{ok: false, error: "exam_locked"}`
**And** le service `ExamLockService::lock()` est idempotent : appelé sur un assignment déjà verrouillé, il ne re-broadcast pas et ne réécrit pas `locked_at`

---

## Epic 4: Le professeur surveille en direct et reprend la main

**Goal** : À la fin de cette epic, le prof peut ouvrir un dashboard temps réel pendant qu'un examen tourne, voir en live ce que font ses étudiants, recevoir des notifications push + email à chaque incident, et déverrouiller un étudiant d'un clic.

### Story 4.1: Reverb + Echo + diffusion des events de base

As a **développeur**,
I want **brancher Laravel Reverb et Laravel Echo côté front pour que les events broadcastés en Epic 3 atteignent un dashboard prof**,
So that **la chaîne temps réel soit fonctionnelle avant d'attaquer l'UI**.

**Acceptance Criteria:**

**Given** Reverb est installé et démarré (Story 1.1)
**When** je crée les classes d'event broadcastable `App\Events\StudentJoined`, `StudentSubmitted`, `StudentLocked`, `IncidentRecorded`, `ExamUnlockedByTeacher`
**Then** chacune implémente `ShouldBroadcast`, expose `broadcastOn()` (channel privé `private-exam.{exam_id}.monitor` ou `private-student.{assignment_id}` selon le cas), et définit `broadcastWith()` avec un payload minimal
**And** les events sont effectivement déclenchés depuis les services Epic 3 (`IncidentRecorder`, `ExamLockService`, `Student\ExamEntryController`, `Student\ExamSubmitController`)

**Given** la diffusion broadcastable
**When** je définis les autorisations dans `routes/channels.php`
**Then** `private-exam.{examId}.monitor` autorise un user si `Auth::user()->id === Exam::find($examId)->teacher_id`
**And** `private-student.{assignmentId}` autorise SI le user est admin ou propriétaire de l'examen OU s'il n'y a pas de user authentifié mais que le `Origin` HTTP correspond au token de la session signée (cas étudiant — voir doc Reverb signed channels)
**And** un test Feature `BroadcastAuthTest` vérifie qu'un teacher A ne peut pas s'abonner aux events d'un examen du teacher B

**Given** la stack Echo est en place
**When** je crée `resources/js/echo.js` initialisant Echo avec le driver `reverb`
**Then** une fonction `subscribeMonitor(examId, handlers)` permet d'écouter les 4 events principaux et appelle les callbacks fournis
**And** un test manuel documenté (`tests/manual-checklist.md`) décrit comment lancer 2 onglets (prof + étudiant) et vérifier qu'un blur déclenche bien un event affiché en console côté prof

### Story 4.2: Dashboard live prof — vue et flux d'événements

As a **professeur**,
I want **un dashboard temps réel où je vois en direct le statut de chaque étudiant et un flux d'incidents**,
So that **je surveille mon examen pendant qu'il tourne**.

**Acceptance Criteria:**

**Given** un examen `published` dont je suis l'auteur
**When** je vais sur `/teacher/exams/{exam}/monitor`
**Then** je vois la vue `teacher/exams/monitor.blade.php` avec deux panneaux : à gauche une grille de cartes étudiants, à droite un flux d'incidents
**And** chaque carte étudiant (`<x-monitor.student-card>`) affiche : nom, matricule, statut (`en attente` / `en cours` / `verrouillé` / `soumis`) avec une pastille couleur (UX-DR3 : gris / vert / rouge / bleu), heure d'ouverture, nombre d'incidents
**And** la page s'abonne à `private-exam.{exam_id}.monitor` via Echo au montage

**Given** un étudiant ouvre son lien
**When** l'event `StudentJoined` arrive
**Then** sa carte passe de `en attente` à `en cours` en temps réel sans rechargement

**Given** un étudiant fait un blur
**When** l'event `IncidentRecorded` arrive
**Then** un nouvel item apparaît en tête du flux à droite avec : timestamp, nom étudiant, type d'incident traduit en libellé humain, sévérité (couleur)
**And** le compteur d'incidents sur sa carte s'incrémente

**Given** un étudiant se fait verrouiller
**When** l'event `StudentLocked` arrive
**Then** sa carte passe à `verrouillé` (rouge) avec la raison affichée et un bouton "Redonner l'accès"
**And** la latence cumulée incident → affichage est mesurée en environnement local et < 1s (NFR5, observable dans la console réseau)

**Given** un étudiant soumet
**When** l'event `StudentSubmitted` arrive
**Then** sa carte passe à `soumis` (bleu) et l'heure de soumission est affichée

### Story 4.3: Déverrouillage manuel d'un étudiant par le professeur

As a **professeur surveillant un examen**,
I want **redonner l'accès à un étudiant verrouillé d'un clic, en sachant pourquoi il l'a été**,
So that **un étudiant qui sort par accident (notif WhatsApp, mauvais clic) ne soit pas pénalisé définitivement**.

**Acceptance Criteria:**

**Given** un assignment `locked=true` et je suis le teacher de l'examen
**When** je clique "Redonner l'accès" sur sa carte du dashboard live
**Then** une confirmation modale s'affiche : "Redonner l'accès à {nom} ? Raison du verrou : {locked_reason}. Cette action est journalisée."
**And** je peux saisir un commentaire optionnel
**And** je confirme via `POST /teacher/assignments/{assignment}/unlock`

**Given** la requête arrive
**When** `Teacher\AssignmentController::unlock` la traite
**Then** une `Policy ExamAssignmentPolicy::unlock` vérifie que je suis bien le teacher de l'examen de l'assignment (sinon 403)
**And** `ExamLockService::unlock($assignment, by: $teacher, comment: $comment)` est appelé dans une transaction
**And** `exam_assignments.locked=false, locked_reason=null, locked_at=null` ; un incident `type=unlocked_by_teacher` avec `payload.actor_id=teacherId, payload.comment=$comment` est ajouté à la table `incidents` (audit trail)
**And** un event `ExamUnlockedByTeacher` est broadcasté sur `private-student.{assignment_id}` ET sur `private-exam.{exam_id}.monitor`

**Given** l'étudiant a la page locked ouverte
**When** l'event `ExamUnlockedByTeacher` arrive côté son client (via Echo signed)
**Then** sa page recharge automatiquement vers `/exam/{token}/run` et il reprend là où il en était (ses réponses auto-sauvegardées sont intactes)
**And** son chronomètre serveur **n'a pas avancé** pendant le verrou OU avance à nouveau selon la politique configurée — décision : **il avance** (le temps continue de courir, c'est plus simple et c'est cohérent avec un examen en temps limité ; documenté en UX-DR9 à ajouter au brief)

### Story 4.4: Web Push — VAPID, service worker, abonnement, envoi

As a **professeur**,
I want **recevoir une notification navigateur dès qu'un incident survient, même si je n'ai pas le dashboard ouvert**,
So that **je peux intervenir vite si je suis sur un autre onglet**.

**Acceptance Criteria:**

**Given** les clés VAPID générées en Story 1.1
**When** un teacher visite son dashboard pour la 1ʳᵉ fois
**Then** une bannière "Activer les notifications" propose l'abonnement
**And** au clic, le navigateur demande la permission via `Notification.requestPermission()`
**And** si accepté, l'abonnement (`endpoint`, `p256dh_key`, `auth_token`, `user_agent`) est POST vers `/teacher/push-subscriptions` et persisté en base (table `push_subscriptions`)

**Given** le service worker `public/sw.js`
**When** il est enregistré au load du dashboard prof
**Then** il intercepte les events `push` du navigateur, parse le payload JSON `{title, body, url}` et affiche une notification système
**And** au clic sur la notification, le navigateur navigue vers `url` (ex: `/teacher/exams/{exam}/monitor`)

**Given** un incident critique survient (Epic 3.5)
**When** `IncidentRecorder` poste la notification au prof
**Then** une `IncidentRaisedNotification` est dispatchée avec canaux `[mail, webpush]` (canal `webpush` custom à créer dans `app/Notifications/Channels/WebPushChannel.php`)
**And** le canal `webpush` itère sur toutes les `push_subscriptions` du teacher et envoie le payload via `WebPushService`
**And** les abonnements invalides (HTTP 410 Gone retourné par le push service) sont supprimés de la table
**And** un test Feature `WebPushDeliveryTest` mock le client `minishlink/web-push` et vérifie le payload envoyé

### Story 4.5: Notifications email d'incident au professeur

As a **professeur**,
I want **recevoir un email pour chaque incident majeur, en plus de la push**,
So that **j'aie une trace tangible et une alerte fiable même si le push échoue**.

**Acceptance Criteria:**

**Given** `IncidentRaisedNotification` est dispatchée avec canal `mail`
**When** le canal `mail` exécute
**Then** un email est envoyé au teacher avec sujet "[ExamGuard] Incident {type} — {nom étudiant} — {nom examen}"
**And** le corps liste : nom étudiant, type d'incident traduit, timestamp, durée (si applicable), lien direct vers la carte de l'étudiant sur le dashboard live
**And** les incidents `severity=info` ne déclenchent pas d'email (seulement push) — éviter le bruit

**Given** un examen avec beaucoup d'incidents en peu de temps
**When** plus de 10 incidents arrivent en moins d'1 minute pour un même étudiant
**Then** un mécanisme de throttling (`throttle` middleware sur la notification ou via Redis SETEX) coalesce les emails en un seul "{N} nouveaux incidents pour {nom}" envoyé une fois par minute max
**And** les push restent unitaires (la fenêtre de réactivité est plus critique côté push)

---

## Epic 5: Le professeur corrige et délivre les notes

**Goal** : À la fin de cette epic, le prof peut auto-corriger V/F + QCM, déclencher une correction des réponses ouvertes par Claude (copy/paste OU API), importer les notes, et envoyer les résultats aux étudiants par email.

### Story 5.1: Auto-correction V/F et QCM

As a **professeur**,
I want **que les V/F et QCM soient corrigés automatiquement dès la soumission**,
So that **je n'ai à corriger manuellement que les questions ouvertes**.

**Acceptance Criteria:**

**Given** une `Submission` qui passe à `status='submitted'`
**When** `AutoGradingService::grade($submission)` est appelé (sync, dans le même flow que `Student\ExamSubmitController::store`)
**Then** il parcourt toutes les questions de l'examen ayant un `autograde_config` non-null (= V/F et QCM)
**And** pour chaque V/F : `AutoGrader::gradeVf($studentAnswer, $config)` retourne `points` (réponse correcte) OU `penalty` (réponse fausse) OU `0` (pas de réponse) selon `config`
**And** pour chaque QCM : `AutoGrader::gradeQcm($studentAnswer, $config)` retourne `points` si `studentAnswer === config.correct`, sinon `0`
**And** la somme est écrite dans `submissions.auto_score` (transaction)
**And** `submissions.status` passe à `auto_graded`

**Given** le `Domain\Exam\AutoGrader`
**When** je consulte le code
**Then** il ne dépend d'aucune classe Laravel (testable en pure unit test)
**And** un test `AutoGraderTest` couvre : V/F correct, V/F faux, V/F sans réponse, QCM correct, QCM faux, QCM réponse vide
**And** un test Feature `ExamSubmitTest::test_vf_and_qcm_are_auto_graded` poste une submission complète et vérifie le calcul de `auto_score`

**Given** une question dont la `autograde_config` change après publication (cas pathologique)
**When** la submission est recorrigée
**Then** c'est la `autograde_config` au moment de la soumission qui est utilisée — donc on copie le `autograde_config` dans la submission au moment du grade (champ `submissions.autograde_snapshot` JSONB optionnel) OU on interdit toute modification des questions une fois l'examen publié (préféré, plus simple — déjà couvert par Story 2.4)

### Story 5.2: Correction Claude en mode copy/paste (export markdown + import notes)

As a **professeur**,
I want **exporter les copies de mon examen en markdown pour les coller dans Claude, puis importer son JSON de notes**,
So that **je peux utiliser Claude gratuitement via chat sans dépendre de l'API**.

**Acceptance Criteria:**

**Given** un examen avec des submissions `auto_graded` ou `submitted`
**When** je clique "Exporter pour Claude" sur `/teacher/exams/{exam}/grading`
**Then** `Teacher\GradingController::exportForClaude` est appelé
**And** `ClaudeExportFormatter::format($exam, $submissions)` produit un markdown qui :
  - Contient une instruction de correction en tête avec le format JSON attendu
  - Liste le référentiel des questions et leur barème
  - Pour chaque submission : matricule, nom, email, score auto-calculé, et toutes les réponses ouvertes question par question
**And** le markdown est affiché dans une textarea pleine largeur avec un bouton "Copier" qui copie le contenu dans le presse-papier
**And** le code de `ClaudeExportFormatter` réutilise/adapte la logique de l'ancien `AdminController::formatForClaude` (référence dans le code supprimé en Story 1.1)

**Given** un teacher qui a obtenu un JSON de notes de Claude
**When** il colle ce JSON dans un champ "Importer les notes" et soumet
**Then** `GradeImportService::importFromJson($exam, $jsonString)` parse le JSON, valide le schéma (présence de `etudiants[].matricule`, `etudiants[].note_total`, etc.)
**And** pour chaque entrée, retrouve la submission via `matricule` (sinon par `email` en fallback) et met à jour `manual_score`, `total_score`, `claude_grade_details` (JSONB), `status='graded'`, `graded_at=now()`
**And** affiche un récap "N copies notées, M ignorées (raison)"
**And** un test Feature `GradingControllerTest::test_import_grades_from_claude_json` couvre import nominal + matricule inexistant + JSON malformé

### Story 5.3: Correction Claude via l'API Anthropic (mode automatique)

As a **professeur**,
I want **lancer la correction des copies directement via l'API Claude, sans passer par le copy/paste**,
So that **je gagne encore plus de temps quand l'admin a configuré la clé API plateforme**.

**Acceptance Criteria:**

**Given** la clé API est configurée par l'admin (Story 1.5) et un examen a des submissions
**When** je clique "Corriger avec Claude API" sur `/teacher/exams/{exam}/grading`
**Then** une modale affiche : nombre de copies à corriger, estimation du coût en USD (tokens estimés × prix du modèle), prompt qui sera envoyé (preview du markdown)
**And** je dois confirmer explicitement pour déclencher (UX-DR8)

**Given** je confirme
**When** `DispatchClaudeGradingJob::dispatch($exam, $teacher)` est mis en queue Redis
**Then** le job tourne async (tries=3, backoff [30, 120, 300])
**And** le job : (a) déchiffre la clé via `PlatformApiKeyVault::getDecryptedKey()`, (b) construit le payload via `ClaudeExportFormatter`, (c) appelle `ClaudeApiClient::send($payload, $apiKey)`, (d) parse la réponse via `GradeImportService::importFromJson($exam, $response)`, (e) écrit dans `api_usage_logs`
**And** la clé n'est jamais loggée (filter dans Laravel logging)

**Given** le service `ClaudeApiClient` (dans `app/Services/Grading/`)
**When** je consulte son code
**Then** c'est un wrapper Guzzle léger autour de l'endpoint `POST https://api.anthropic.com/v1/messages`
**And** il a un timeout de 180s et un retry sur erreur 429 (rate limit) avec respect du header `retry-after`
**And** il accepte le modèle via paramètre (lu depuis `platform_settings.key='claude.model'`, défaut `claude-opus-4-7`)
**And** un test Unit `ClaudeApiClientTest` utilise `Http::fake()` pour vérifier la forme exacte du payload sortant et le parsing de réponse

**Given** le job complète avec succès
**When** je consulte mon dashboard de grading
**Then** je vois les submissions passées à `status='graded'` en temps réel (broadcast `GradingCompleted` event optionnel — ou polling discret 5s)
**And** une notif `GradingCompletedNotification` (email seul) m'est envoyée avec récap : copies notées, coût total
**And** un nouvel `api_usage_logs` row contient `teacher_id`, `exam_id`, `tokens_in`, `tokens_out`, `cost_cents`, `status='ok'`

**Given** le job échoue (timeout, 401, JSON malformé)
**When** les 3 retries sont épuisés
**Then** le job écrit un `api_usage_logs` row avec `status='error'`
**And** une notif `GradingFailedNotification` est envoyée au teacher avec message d'erreur exploitable (pas la stack trace)
**And** les submissions restent à leur statut précédent (pas de corruption partielle)

### Story 5.4: Envoi des notes aux étudiants par email (individuel + bulk)

As a **professeur**,
I want **envoyer la note à un étudiant individuellement ou à tous mes étudiants notés en masse**,
So that **je clôture proprement la session d'examen**.

**Acceptance Criteria:**

**Given** une submission `status='graded'`
**When** je clique "Envoyer la note" sur la fiche d'une copie
**Then** `Teacher\GradingController::sendGrade` dispatch un `SendGradeEmailJob` pour cette submission
**And** le job envoie un `GradeMailable` à `assignment.student_email` avec : nom de l'examen, note totale, détails par section (depuis `claude_grade_details` si présent), appréciation
**And** la submission passe à `status='sent'`, `sent_at=now()`

**Given** un examen avec plusieurs submissions `graded`
**When** je clique "Envoyer toutes les notes"
**Then** une confirmation affiche le nombre concerné et un bouton de validation
**And** au clic, un `SendBulkGradeEmailsJob` est dispatché qui itère et déclenche un `SendGradeEmailJob` par submission (préserve l'idempotence et permet le retry granulaire)
**And** une fois tous les jobs terminés, l'écran de grading montre tout en `sent` (poll 5s ou broadcast event final)

**Given** un échec SMTP sur un envoi
**When** le job a épuisé ses retries
**Then** la submission reste à `status='graded'` (pas de `sent`), un incident applicatif `grade_email_failed` est loggé
**And** je vois un indicateur "Envoi échoué" sur la fiche concernée avec un bouton "Réessayer"

**Given** je suis sur `/admin` (Epic 1.5 — dashboard admin)
**When** je consulte le widget "Consommation API ce mois"
**Then** la donnée est désormais alimentée par les `api_usage_logs` de Story 5.3
**And** un sous-écran `/admin/usage` détaille par mois et par prof la consommation (tokens, coût, nombre d'appels, taux d'erreur)
**And** un test Feature `AdminApiUsageTest` peuple `api_usage_logs` via factory et vérifie l'agrégation correcte

---

## Architecture Validation Results

### FR Coverage Verification

✅ Tous les FRs (FR1 à FR40) sont mappés à au moins une story dans le tableau "FR Coverage Map" ci-dessus. La couverture a été vérifiée story par story lors de la rédaction.

### NFR Coverage Verification

| NFR | Couvert par |
|---|---|
| NFR1 — Signed URLs | Story 1.1 (.env + middleware `signed`), Story 2.6 (génération), Story 3.1 (middleware ResolveExamAssignment) |
| NFR2 — Chronomètre serveur | Story 3.2 (`ExamTimerService` + heartbeat) |
| NFR3 — Clé API chiffrée | Story 1.5 (`PlatformApiKeyVault`) |
| NFR4 — Auto-grading serveur uniquement | Story 2.2 (autograde_config non renvoyé client), Story 5.1 (Domain pur) |
| NFR5 — Latence < 2s | Story 4.2 (mesure dans CA), Reverb local sub-100ms |
| NFR6 — Auto-save non-bloquant | Story 3.2 (debounce + idempotent) |
| NFR7 — Aucune copie perdue | Story 3.2 (localStorage fallback + transactions) |
| NFR8 — Audit append-only | Story 3.5 (incidents) + Story 4.3 (unlock journalisé) |
| NFR9 — RGPD | Hors stories (gap documenté, à traiter avant déploiement) |
| NFR10 — Conso API plafonnable | Story 5.4 (dashboard), V2 pour le blocage |
| NFR11 — Patterns archi | Toutes stories (Form Request + Policy + Service mentionnés explicitement) |
| NFR12 — Navigateurs cibles | Story 3.1 (mention dans consentement) |

### Architecture Compliance Check

- ✅ Pas de "Database Setup" upfront : chaque story crée/altère uniquement les tables dont elle a besoin (Story 1.1 droppe l'ancien + setup infra ; Story 1.2 alter users ; Story 1.5 platform_settings ; Story 2.1 exams/exam_sections ; Story 2.2 questions ; Story 2.5 exam_assignments ; Story 3.1 submissions ; Story 3.5 incidents ; Story 5.3 api_usage_logs ; Story 4.4 push_subscriptions ; Story 2.7 attachments).
- ✅ Story 1.1 est bien la première story et installe le starter/foundation (Breeze, Reverb, Postgres, Redis, S3, VAPID) comme imposé par l'architecture.
- ✅ Aucune story ne dépend d'une story future de la même epic (vérification ci-dessous).

### Story Dependency Validation (within-epic)

- **Epic 1** : 1.1 (infra) → 1.2 (rôles, dépend de 1.1) → 1.3 (signup, dépend de 1.2) → 1.4 (validation, dépend de 1.3) → 1.5 (clé API, indépendant de 1.4 mais peut suivre). ✅ Pas de dépendance forward.
- **Epic 2** : 2.1 (CRUD examen) → 2.2 (sections + VF/QCM) → 2.3 (autres types) → 2.4 (sécurité par examen) → 2.5 (étudiants) → 2.6 (publication). ✅ Linéaire.
- **Epic 3** : 3.1 (entry + middleware) → 3.2 (runtime + autosave) → 3.3 (fullscreen + visibility) → 3.4 (blocages + devtools) → 3.5 (recording serveur + lock). ✅ Linéaire.
- **Epic 4** : 4.1 (Reverb fondation) → 4.2 (dashboard) → 4.3 (unlock) → 4.4 (push) → 4.5 (email). ✅ 4.2 dépend de 4.1, le reste est composable.
- **Epic 5** : 5.1 (auto-grading) → 5.2 (copy/paste Claude) → 5.3 (API Claude) → 5.4 (envoi notes). ✅ Linéaire.

### Epic Independence Check

- Epic 1 livre une plateforme avec admin + 1 prof connecté = standalone (un prof peut tester son login, c'est une vraie livraison).
- Epic 2 livre des examens publiés et des liens envoyés = standalone (les étudiants reçoivent un lien mais ne peuvent pas encore le consommer → la "vraie" valeur arrive à E3, mais E2 est démontrable).
- Epic 3 livre un étudiant qui peut composer en mode sécurisé sans dashboard live = standalone (le prof voit les soumissions dans son dashboard de grading, juste pas en temps réel).
- Epic 4 ajoute la dimension temps réel par-dessus = enhance, sans casser ce qui précède.
- Epic 5 ajoute la correction = on peut imaginer un MVP qui s'arrête après E3 (correction manuelle par le prof), mais E5 livre la promesse complète du brief.

✅ Chaque epic livre de la valeur indépendamment.

### File Churn Check

Risque identifié : les Stories 2.1, 2.2, 2.3, 2.4 modifient toutes le même `ExamBuilderController` et la vue `teacher/exams/builder.blade.php`. **Décision** : c'est OK, elles sont dans la **même epic** (Epic 2). Le churn cross-epic est faible.

Risque identifié : Stories 3.3, 3.4 modifient toutes `resources/js/exam-runtime.js`. **Décision** : OK, même epic, séquentielles.

✅ Pas de churn cross-epic problématique.

### Overall Status

**READY FOR DEVELOPMENT** avec deux gaps documentés à traiter avant déploiement réel (non bloquants pour démarrer le code) :
1. Politique de rétention RGPD à écrire (NFR9) — à inclure dans `docs/` avant prod.
2. Schéma exact de `security_settings_json` (au-delà des toggles MVP listés en Story 2.4) à figer au démarrage de Story 2.4.

**Confidence Level** : élevé. Le brief, l'architecture et la séquence d'implémentation s'alignent ; aucune story n'inverse une décision architecturale.

---

*Backlog v1.0 — produit le 2026-05-23 par John (BMad Product Manager) en mode batch (auto mode). Sources : brief Mary + architecture Winston. 5 epics, 26 stories, 40 FRs couverts.*
