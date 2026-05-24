---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
inputDocuments:
  - _bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/brief.md
  - _bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/addendum.md
  - C:/Users/Mounkaila/PhpstormProjects/exam (code Laravel existant)
workflowType: architecture
project_name: ExamGuard
user_name: Mounkaila
date: 2026-05-23
status: complete
lastStep: 8
completedAt: 2026-05-23
---

# Architecture Decision Document — ExamGuard

> Document généré par Winston (BMad System Architect) en mode batch (auto mode). Sections produites en un seul passage ; gates utilisateur consolidés en revue finale.
> Brief source : `_bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/brief.md`
> Codebase de départ : projet Laravel existant `C:/Users/Mounkaila/PhpstormProjects/exam`

---

## 1. Project Context Analysis

### 1.1 Requirements Overview

**Functional Requirements extraits du brief (FR groupés)**

- **FR-Auth** : signup/login professeur, validation manuelle par l'admin, login admin, pas de compte étudiant (accès par token signé).
- **FR-ExamBuilder** : builder visuel section-par-section avec types V/F, QCM, réponse courte, dissertation, code, dépôt de fichier ; barème par question ; configuration durée, fenêtre d'ouverture, liste étudiants.
- **FR-StudentAssignment** : génération de liens uniques nominatifs à usage unique, envoi par email, page de consentement préalable.
- **FR-ExamRuntime** : environnement verrouillé (plein écran forcé, blocage copier/coller/clic droit/raccourcis/DevTools), Visibility API, chronomètre serveur, auto-save des réponses.
- **FR-AntiCheat** : verrouillage automatique de l'examen à la 1ʳᵉ infraction majeure, journal d'incidents typés, déverrouillage manuel par le prof.
- **FR-LiveMonitor** : dashboard temps réel pour le prof (présence, statut, flux d'événements, action de déverrouillage).
- **FR-Notifications** : notification push navigateur + email à chaque incident pour le prof concerné.
- **FR-AutoGrading** : auto-correction V/F + QCM côté serveur (logique configurable par question).
- **FR-ClaudeGrading** : correction hybride — export markdown pour copy/paste OU appel API Anthropic avec clé plateforme mutualisée.
- **FR-GradeDelivery** : envoi automatique des notes par email aux étudiants, individuellement ou en masse.
- **FR-AdminConsole** : validation des inscriptions profs, gestion de la clé API plateforme, monitoring de la consommation API.

**Non-Functional Requirements**

| Catégorie | Exigence | Implication architecturale |
|---|---|---|
| Sécurité | Token étudiant à usage unique, signed URL, IP/UA loggés | Middleware dédié + URL signée Laravel + journal d'audit |
| Sécurité | Chronomètre serveur source de vérité | Calcul `remaining = closes_at - now()` côté serveur à chaque tick |
| Sécurité | Clé API Claude stockée chiffrée | `Crypt::encryptString` + table `platform_settings` |
| Sécurité | Pas de fuite des bonnes réponses au client | Logique d'auto-grading exclusivement serveur, payload client = `(question_id, value)` seulement |
| Performance | Latence dashboard live < 2 s entre incident client et affichage prof | WebSocket (Laravel Reverb) avec broadcast immédiat |
| Performance | Auto-save étudiant non-bloquant | Endpoint AJAX idempotent + debounce client |
| Disponibilité | Aucune copie perdue | Transactions DB sur chaque save, fallback localStorage côté client |
| Conformité | Journal d'audit complet par soumission | Table `incidents` immuable (append-only en pratique) |
| Conformité | RGPD : rétention des copies & incidents | Politique de purge à documenter (hors MVP code mais documenté) |
| Coût | Consommation API Anthropic visible et plafonnable | Table `api_usage_log` + dashboard admin |

### 1.2 Scale & Complexity

- **Domaine technique** : web full-stack (Laravel server-rendered + Alpine.js + WebSocket live).
- **Niveau de complexité** : **moyen-élevé**. Multi-tenant (profs isolés), real-time (Reverb), sécurité examen multicouche, intégration LLM, gestion fichiers joints.
- **Composants architecturaux estimés** : ~6 modules métier (Auth, ExamBuilder, AssignmentDelivery, ExamRuntime, LiveMonitor, Grading) + 3 modules transverses (Security, Notification, AdminConsole).

### 1.3 Technical Constraints & Dependencies

**Contraintes héritées du code existant (non-négociables)**
- Laravel 13 + PHP 8.3 (composer.json).
- Tailwind 4 + Vite 8 (package.json).
- Pattern Blade + assets compilés (pas de SPA).
- Pattern auto-correction V/F + QCM avec scoring côté serveur (existant dans `SubmissionController`).
- Pattern export markdown formaté pour Claude (existant dans `AdminController::formatForClaude`).

**Dépendances externes**
- API Anthropic Claude (clé mutualisée, payée par l'admin).
- Service SMTP (Mailable Laravel existant).
- Service de file storage S3-compatible (à provisionner — fichiers joints questions/réponses).
- Service de push web (VAPID, pas de tiers requis — `minishlink/web-push`).

### 1.4 Cross-Cutting Concerns

- **Autorisation** : un prof ne doit jamais voir l'examen / les copies d'un autre prof → Policies Laravel sur chaque modèle métier.
- **Audit trail** : chaque incident, déverrouillage, appel API → journalisé avec acteur + timestamp.
- **Real-time fan-out** : événements broadcast doivent atteindre le bon prof et seulement lui (channels privés autorisés).
- **Sécurité runtime étudiant** : couche client (Alpine) + couche serveur (middleware) ; ne jamais faire confiance au client.
- **Idempotence** : auto-save réponses, déverrouillages, soumissions → idempotents (retry-safe).
- **Encryption-at-rest** : clé API Claude + tokens signés.

---

## 2. Starter Template Evaluation

### 2.1 Decision: pas de starter externe — on **étend l'existant**

Le projet Laravel a déjà été initialisé (`composer.json`, `package.json`, structure `app/`/`routes/`/`resources/views/`, migrations en place, controllers initiaux). **Repartir d'un starter externe (Jetstream, Breeze, Laravel Boost) signifierait jeter du code utile.** Décision : on conserve la base et on **ajoute** les briques manquantes via composer.

### 2.2 Composer additions au socle (MVP)

| Package | Version | Rôle |
|---|---|---|
| `laravel/breeze` (Blade stack) | ^2.3 | Auth scaffolding (signup, login, password reset, email verification) — choix Blade car cohérent avec absence de SPA |
| `laravel/reverb` | ^1.0 | WebSocket server officiel Laravel pour broadcasts |
| `minishlink/web-push` | ^9.0 | Notifications Web Push VAPID |
| `league/flysystem-aws-s3-v3` | ^3.0 | Driver S3-compatible (fichiers joints) |
| `predis/predis` | ^2.0 | Client Redis (cache/queue) |
| `guzzlehttp/guzzle` | ^7.9 | Déjà transitive — utilisée pour l'API Anthropic |

**Pas retenu** :
- `laravel/sanctum` — pas d'API publique au MVP (les endpoints étudiants sont signed-URL token-based, pas Bearer-token).
- `inertiajs/inertia-laravel` — SPA non requis, Blade + Alpine suffisent.
- SDK Anthropic tiers — pas de SDK officiel PHP, on écrit un client Guzzle léger et auditable.

### 2.3 Frontend additions

| Package | Version | Rôle |
|---|---|---|
| `alpinejs` | ^3.14 | Interactivité légère sur Blade (builder, dashboard live) |
| `laravel-echo` | ^1.16 | Client WebSocket pour Reverb |
| `pusher-js` | ^8.4 | Driver utilisé par Echo/Reverb |

### 2.4 Infrastructure additions

- **PostgreSQL 16** en remplacement de SQLite (concurrence multi-prof, JSON colonnes typées, transactions sérialisables sur le déverrouillage).
- **Redis 7** pour cache + queues + sessions.
- **Supervisor** sur l'hôte pour superviser `php artisan reverb:start`, `php artisan queue:work`, `php artisan schedule:work`.
- **MinIO** en dev (S3-compatible local) ; **S3** (ou compatible : Backblaze B2, Scaleway Object Storage) en prod.

### 2.5 Note d'implémentation

La première story du sprint doit être **"Setup infrastructure et auth"** :
```bash
composer require laravel/breeze:^2.3 laravel/reverb:^1.0 minishlink/web-push:^9.0 league/flysystem-aws-s3-v3:^3.0 predis/predis:^2.0
php artisan breeze:install blade
php artisan reverb:install
npm install alpinejs@^3.14 laravel-echo@^1.16 pusher-js@^8.4
```
Puis migration SQLite → PostgreSQL via `php artisan migrate:fresh` sur la nouvelle DB (rappel : on repart d'une base vide, donc pas de migration de données).

---

## 3. Core Architectural Decisions

### 3.1 Critical Decisions (bloquantes pour l'implémentation)

| # | Décision | Choix | Rationale |
|---|---|---|---|
| C1 | Stockage relationnel | **PostgreSQL 16** | JSON colonnes natives typées, transactions, concurrence multi-prof, support Eloquent natif Laravel. SQLite ne tient pas la charge WebSocket + concurrence. |
| C2 | Auth | **Laravel Breeze (Blade)** | Boring, complet (signup/login/verif/reset), s'intègre dans nos vues Blade sans SPA. |
| C3 | Autorisation | **Policies Laravel + role enum** sur `users.role` | Pattern natif, expressif, testable. Pas de Spatie/Permission au MVP : 2 rôles suffisent. |
| C4 | Real-time | **Laravel Reverb** + Echo + Pusher protocol | Service officiel Laravel, pas de dépendance SaaS. Auto-hébergé. |
| C5 | Web Push | **VAPID via `minishlink/web-push`** | Pas de service tiers (FCM, OneSignal) → souveraineté + zéro coût récurrent. |
| C6 | LLM API client | **Wrapper Guzzle maison** sur `ClaudeApiClient` | Pas de SDK officiel PHP fiable ; un client de 100 lignes auditable et testable est préférable. |
| C7 | File storage | **S3-compatible via flysystem-s3** | Standard Laravel. MinIO en dev, S3/compatible en prod. |
| C8 | Queues | **Redis driver** (Laravel queues) | Performance, support natif Reverb broadcasts, retry built-in. |
| C9 | Architecture front | **Blade + Alpine.js** (pas de SPA) | Boring, server-rendered, SEO non requis, équipe Laravel solo → minimise la surface. |
| C10 | Token étudiant | **URL signée Laravel** liée à `(exam_assignment_id, nonce)` | Mécanisme natif, signé HMAC, non-falsifiable. |
| C11 | Chronomètre | **Calculé serveur**, exposé via endpoint `GET /student/{token}/heartbeat` | Source de vérité unique. Client n'est qu'un affichage. |

### 3.2 Important Decisions (forment l'architecture)

| # | Décision | Choix | Rationale |
|---|---|---|---|
| I1 | Modèle de données examens | **Hiérarchie `exam → sections → questions`** | Correspond au builder visuel "section par section" du brief. Plus propre que tout aplatir dans `questions`. |
| I2 | Sérialisation réponses | **JSON column `submissions.answers_json`** | Schéma de réponses hétérogène (V/F, QCM, texte, code, fichier référence). JSON natif PostgreSQL. |
| I3 | Configuration auto-grading | **JSON `questions.autograde_config`** | `{"type":"vf","correct":"VRAI","pts":1,"penalty":-0.5}` — flexible, ne nécessite pas de table dédiée. |
| I4 | Verrouillage examen étudiant | **Flag `exam_assignments.locked` + raison + timestamp** | Une seule source de vérité. Le middleware `ExamIsLive` lit ce flag à chaque requête. |
| I5 | Communication client → serveur (incidents) | **POST AJAX `/student/{token}/incidents`** avec corps `{type, payload}` | Simple, retry possible, journalisé. WebSocket bidirectionnel pas requis côté étudiant. |
| I6 | Communication serveur → prof (live) | **Reverb broadcast events** sur channel privé `private-exam.{id}.monitor` | Fan-out efficace, autorisation via `routes/channels.php`. |
| I7 | Communication serveur → étudiant (unlock) | **Reverb private-channel `private-student.{assignment_id}`** | Permet au prof de déverrouiller sans que l'étudiant ait à recharger. |
| I8 | Encryption clé API plateforme | **`Crypt::encryptString`** sur `platform_settings.encrypted_value` | App_key Laravel = source de la clé symétrique. Rotation de la clé Anthropic = `UPDATE platform_settings` côté admin. |
| I9 | Job Claude grading | **Queue async** (`DispatchClaudeGrading`) avec retry 3× backoff exponentiel | Appel API long (60-180 s) → ne doit pas bloquer une requête HTTP. |
| I10 | Mailable Laravel | **Conservé** (`Mail::send` existant adapté) | Pattern déjà éprouvé dans `AdminController::sendGrade`. Refactor en `Mailable` class propre. |
| I11 | Auto-save étudiant | **AJAX POST debouncé 500 ms** sur `/student/{token}/answers` | Une seule ligne `submissions` par assignment, mise à jour par patch JSON. |
| I12 | Fichiers joints (étudiants) | **Upload AJAX direct vers `/student/{token}/attachments`** → S3 via signed URL backend | Évite de bourrer la table `submissions` avec des blobs. |

### 3.3 Deferred Decisions (V2+)

- **Multi-tenant institutionnel** (un admin par institution, branding, sous-domaines) — V2.
- **Quotas API par prof / par examen** — V2, mais le `api_usage_log` est posé dès le MVP pour les supporter.
- **Banque de questions partagée** — V2.
- **Proctoring webcam** — V3.
- **Internationalisation i18n** — V2 (le code MVP est en français hardcodé, mais on utilise déjà les helpers de localisation Laravel pour préparer le terrain).
- **Mobile app native** — non prévue, mobile web suffit.

### 3.4 Implementation Sequence Recommandé

1. **Migration infra** : composer add + breeze install + reverb install + Postgres + Redis.
2. **Domaine `users` + admin console minimale** : roles, validation profs, settings plateforme (clé API).
3. **Domaine `exams` (CRUD + builder visuel)** : schema + Blade builder + Alpine.
4. **Domaine `exam_assignments` + envoi liens** : génération token signé + Mailable.
5. **Runtime étudiant non-sécurisé** (vue Blade fonctionnelle) + auto-save.
6. **Couche sécurité runtime** (Visibility / Fullscreen / blocages / incident reporter).
7. **Verrouillage + déverrouillage** (broadcasts + middleware `ExamIsLive`).
8. **Dashboard live prof** (Echo + Reverb + Alpine).
9. **Web Push** (VAPID + service worker).
10. **Auto-grading + GradingController** (V/F + QCM serveur).
11. **Claude grading** (export markdown + API hybride).
12. **Envoi des notes** (Mailable + bulk).

### 3.5 Cross-Component Dependencies

- **ExamRuntime ↔ Security/IncidentRecorder** : tout incident remonte serveur, déclenche `IncidentRecorded` event → broadcast Reverb.
- **LiveMonitor ↔ Reverb** : abonné aux events broadcast ; sans Reverb pas de monitor.
- **GradingService ↔ ApiKeyVault** : ne déchiffre la clé API qu'au moment de l'appel, jamais en cache.
- **AssignmentTokenGenerator ↔ Middleware `ResolveExamAssignment`** : couplés par la signature URL.

---

## 4. Implementation Patterns & Consistency Rules

### 4.1 Naming Patterns

**Database (PostgreSQL)**
- Tables : `snake_case`, **plural** (`exams`, `exam_assignments`, `incidents`).
- Colonnes : `snake_case` (`teacher_id`, `created_at`, `locked_reason`).
- Foreign keys : `{singular_table}_id` (`exam_id`, `user_id`).
- Index : préfixe `idx_` + table + colonnes (`idx_incidents_assignment_occurred`).
- Enums PostgreSQL : déclarés en migration via `DB::statement("CREATE TYPE ...")` OU castés via Eloquent `Casts\Enum` (préféré).

**API / Routes**
- Routes web admin : `/admin/{resource}` (`/admin/teachers`, `/admin/settings`).
- Routes web prof : `/teacher/{resource}` (`/teacher/exams`, `/teacher/exams/{exam}/monitor`).
- Routes web étudiant : `/exam/{token}/...` (token signé, jamais d'`id` exposé).
- Routes API internes (AJAX) : `/api/student/{token}/{action}` (`/api/student/{token}/answers`).
- Paramètres de route : `{exam}`, `{assignment}` (binding implicite Eloquent).

**Code PHP**
- Classes : `PascalCase` (`ExamBuilderController`, `ClaudeApiClient`).
- Méthodes : `camelCase` (`recordIncident`, `lockAssignment`).
- Variables : `camelCase` (`$examId`, `$rawAnswer`).
- Enums : `PascalCase` valeurs `SNAKE_CASE` (`QuestionType::SHORT_ANSWER`).
- Form Requests : suffixe `Request` (`StoreExamRequest`).
- Services : suffixe `Service` (`GradingService`, `IncidentRecorder` — sans suffixe quand le nom porte déjà le sens).

**Frontend**
- Fichiers Blade : `kebab-case.blade.php` (`exam-builder.blade.php`).
- Composants Blade : `kebab-case` invoqués `<x-exam-builder.section ... />`.
- Fichiers JS : `kebab-case.js` (`exam-runtime.js`, `live-monitor.js`).
- Variables Alpine : `camelCase` (`x-data="examRuntime()"`).
- Classes Tailwind : pas de wrappers custom, Tailwind utility-first direct.

**Events broadcastés**
- Nom de classe : `PascalCase` + verbe au passé (`StudentJoined`, `IncidentRecorded`, `ExamUnlockedByTeacher`).
- Channel privé : `private-{contexte}.{id}` (`private-exam.{id}.monitor`, `private-student.{assignment_id}`).
- Type d'incident côté DB : `snake_case` (`tab_blur`, `fullscreen_exit`, `devtools_detected`, `copy_attempt`, `paste_attempt`, `link_reopen`, `ip_change`, `multiple_session`).

### 4.2 Structure Patterns

- **Tests** : `tests/Feature/{Domain}/` (PHPUnit). Pattern Arrange-Act-Assert. Pas de co-localisation `*.test.php`.
- **Domain code pur** (enums, value objects, services sans Laravel) : `app/Domain/{BoundedContext}/`.
- **Services Laravel** (avec deps framework) : `app/Services/{Domain}/`.
- **Form Requests** : `app/Http/Requests/{Domain}/{Action}Request.php`.
- **Migrations** : 1 migration par changement, datée + verbe action (`2026_06_01_120000_create_exams_table.php`).
- **Seeders** : `database/seeders/{Domain}Seeder.php`. Pas de seeders en prod sauf `AdminUserSeeder` initial.

### 4.3 Format Patterns

**Réponses API internes (AJAX)**
- Succès : `{ "ok": true, "data": <payload> }`, HTTP 200.
- Erreur validation : `{ "ok": false, "errors": { "field": ["message"] } }`, HTTP 422.
- Erreur autorisation : `{ "ok": false, "error": "forbidden" }`, HTTP 403.
- Erreur métier : `{ "ok": false, "error": "exam_locked", "message": "..." }`, HTTP 409.
- Erreur serveur : `{ "ok": false, "error": "server_error" }`, HTTP 500.

**Données JSON en base**
- Toujours en `snake_case` côté DB (cohérent avec colonnes).
- Dates : ISO 8601 UTC (`2026-05-23T18:42:00Z`).
- Booléens : `true`/`false` natifs (pas de `0`/`1` dans le JSON).
- Argent / coûts : entiers en centimes (`cost_cents`) pour éviter les flottants.

**Structure payload `incidents.payload_json`** (exemples)
```json
// type: tab_blur
{ "duration_ms": 1450, "tab_count_hint": 2 }

// type: fullscreen_exit
{ "method": "esc_key" | "f11" | "external", "elapsed_ms": 12340 }

// type: copy_attempt
{ "selection_length": 87 }
```

### 4.4 Communication Patterns

**Events broadcastés (Reverb)**
- Payload event : minimal — `{ assignmentId, type, occurredAt, summary }`. Le détail est rechargé via API par le client si besoin (évite de balader des PII dans le canal).
- Sérialisation : implements `ShouldBroadcast` + méthode `broadcastWith()` explicite.
- Authorization : `routes/channels.php` — vérifie que le `user_id` matché correspond bien au teacher de l'examen / à l'assignment.

**State management côté Alpine**
- Pas de store global type Pinia/Vuex. Un composant Alpine par vue (`examRuntime()`, `liveMonitor()`).
- Updates immutables (jamais de mutation in-place sur `$persist` data).
- Pas de `localStorage` pour données sensibles ; OK pour fallback auto-save des réponses (chiffré via la clé serveur ? — non, c'est un fallback de secours, en clair, accepté).

### 4.5 Process Patterns

**Error handling**
- Toujours via `try / catch` autour des appels externes (Claude API, S3, SMTP).
- Logs : `Log::error('Claude API failed', ['exception' => $e, 'context' => $context])`.
- User-facing : message générique côté UI + détail dans le log + Sentry hook (post-MVP).
- Pas de `Response::json([...], 500)` direct dans les controllers — passe par `Handler::render()` Laravel.

**Loading states côté UI**
- Pattern Alpine : variable booléenne locale `loading: false` + `<button :disabled="loading">`.
- Indicateurs visuels : spinner inline Tailwind (`animate-spin`), jamais d'overlay full-page.

**Retry / idempotence**
- Auto-save réponses : idempotent (un PUT logique sur la submission). Le client retry silencieusement 3× avant alerter.
- Verrouillage : idempotent (`lock_assignment(assignment_id, reason)` peut être appelé N fois, ne re-lock pas).
- Job queue : `tries = 3`, `backoff = [10, 60, 300]` (secondes). Échec final → log + email admin.

### 4.6 Enforcement Guidelines

**Tous les agents AI / contributeurs MUST :**
- Utiliser les Form Requests pour toute validation, jamais `$request->validate()` inline dans le controller.
- Passer par Policies pour toute autorisation, jamais de `if ($user->id !== $exam->teacher_id) abort(403)` ad hoc.
- Broadcaster les events via classes typées (`ShouldBroadcast`), jamais `broadcast(['raw' => ...])`.
- Stocker les secrets via `Crypt::encryptString` quand persistés en DB, via `.env` quand statiques.
- Ne JAMAIS exposer un `exam_assignment.id` numérique dans une URL côté étudiant — toujours le token signé.

**Anti-patterns interdits :**
- Magic strings pour les types d'incident → toujours via enum `IncidentType`.
- `Exam::find($id)` quand on devrait avoir un binding implicite + policy.
- Logique d'auto-grading côté JS (le client doit ignorer la "bonne réponse").
- Appels API Claude synchrones dans une requête HTTP (queue obligatoire).
- Écriture directe dans `$model->property = ...; $model->save()` sans passer par un service quand on traverse plusieurs tables (transaction nécessaire).

---

## 5. Project Structure & Boundaries

### 5.1 Arborescence cible

```
exam/                                  # racine projet (renommer en examguard/ post-MVP)
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── PurgeExpiredAssignments.php
│   │       └── DispatchExamReminders.php
│   ├── Domain/                        # logique métier pure
│   │   ├── Exam/
│   │   │   ├── ExamStatus.php         # Enum: draft|published|closed
│   │   │   ├── QuestionType.php       # Enum: vf|qcm|short|essay|code|file_upload
│   │   │   └── AutoGrader.php         # méthodes statiques: gradeVf, gradeQcm
│   │   ├── Incident/
│   │   │   ├── IncidentType.php       # Enum
│   │   │   └── IncidentSeverity.php   # Enum: info|warning|critical
│   │   └── Grading/
│   │       └── ScoreCalculator.php
│   ├── Events/
│   │   ├── StudentJoined.php          # → private-exam.{id}.monitor
│   │   ├── StudentSubmitted.php
│   │   ├── StudentLocked.php
│   │   ├── IncidentRecorded.php
│   │   └── ExamUnlockedByTeacher.php  # → private-student.{assignment_id}
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── TeacherController.php
│   │   │   │   ├── PlatformSettingsController.php
│   │   │   │   └── ApiUsageController.php
│   │   │   ├── Auth/                  # généré par Breeze
│   │   │   ├── Teacher/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ExamBuilderController.php       # CRUD examen + sections + questions
│   │   │   │   ├── ExamPublishController.php       # publish + envoi liens
│   │   │   │   ├── ExamMonitorController.php       # dashboard live
│   │   │   │   ├── AssignmentController.php        # déverrouillage manuel
│   │   │   │   ├── GradingController.php           # export Claude + import notes + send emails
│   │   │   │   └── PushSubscriptionController.php
│   │   │   └── Student/
│   │   │       ├── ExamEntryController.php         # GET /exam/{token} — consent screen
│   │   │       ├── ExamRuntimeController.php       # GET /exam/{token}/run — shell verrouillé
│   │   │       ├── ExamAnswerController.php        # POST /api/student/{token}/answers
│   │   │       ├── ExamSubmitController.php        # POST /exam/{token}/submit
│   │   │       ├── ExamHeartbeatController.php     # GET /api/student/{token}/heartbeat
│   │   │       └── IncidentReportController.php    # POST /api/student/{token}/incidents
│   │   ├── Middleware/
│   │   │   ├── EnsureAdminRole.php
│   │   │   ├── EnsureTeacherIsActive.php
│   │   │   ├── ResolveExamAssignment.php
│   │   │   └── ExamIsLive.php                      # vérifie token, état, lock
│   │   ├── Requests/
│   │   │   ├── Admin/
│   │   │   │   ├── ApproveTeacherRequest.php
│   │   │   │   └── UpdatePlatformApiKeyRequest.php
│   │   │   ├── Teacher/
│   │   │   │   ├── StoreExamRequest.php
│   │   │   │   ├── StoreQuestionRequest.php
│   │   │   │   └── ImportGradesRequest.php
│   │   │   └── Student/
│   │   │       ├── SaveAnswersRequest.php
│   │   │       ├── SubmitExamRequest.php
│   │   │       └── ReportIncidentRequest.php
│   │   └── Resources/                              # (post-MVP si besoin d'API publique)
│   ├── Jobs/
│   │   ├── SendAssignmentEmailJob.php
│   │   ├── DispatchClaudeGradingJob.php
│   │   ├── SendGradeEmailJob.php
│   │   └── PurgeExpiredAssignmentsJob.php
│   ├── Mail/
│   │   ├── ExamAssignmentMailable.php
│   │   ├── GradeMailable.php
│   │   └── TeacherActivationMailable.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── PlatformSetting.php
│   │   ├── Exam.php
│   │   ├── ExamSection.php
│   │   ├── Question.php
│   │   ├── ExamAssignment.php
│   │   ├── Submission.php
│   │   ├── Attachment.php
│   │   ├── Incident.php
│   │   ├── ApiUsageLog.php
│   │   └── PushSubscription.php
│   ├── Notifications/
│   │   ├── IncidentRaisedNotification.php          # canaux: mail + webpush
│   │   ├── TeacherApprovedNotification.php
│   │   └── ExamReadyNotification.php
│   ├── Policies/
│   │   ├── ExamPolicy.php
│   │   ├── ExamAssignmentPolicy.php
│   │   └── SubmissionPolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── BroadcastServiceProvider.php
│   │   └── EventServiceProvider.php
│   └── Services/
│       ├── Exam/
│       │   ├── ExamBuilderService.php
│       │   ├── ExamPublisherService.php
│       │   ├── AssignmentTokenGenerator.php        # signed URL Laravel
│       │   └── ExamTimerService.php                # remaining time calc
│       ├── Grading/
│       │   ├── AutoGradingService.php
│       │   ├── ClaudeExportFormatter.php           # markdown
│       │   ├── ClaudeApiClient.php                 # wrapper Guzzle
│       │   ├── ClaudeGradingService.php            # orchestrateur (queue)
│       │   └── GradeImportService.php
│       ├── Security/
│       │   ├── PlatformApiKeyVault.php             # encrypt/decrypt
│       │   ├── IncidentRecorder.php                # crée + broadcast
│       │   └── ExamLockService.php                 # lock / unlock atomique
│       └── Notification/
│           ├── WebPushService.php
│           └── PushSubscriptionService.php
├── bootstrap/
├── config/
│   ├── app.php
│   ├── broadcasting.php                # config Reverb
│   ├── webpush.php                     # config VAPID
│   ├── claude.php                      # endpoint, modèle, paramètres
│   └── ...
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── ExamFactory.php
│   │   ├── QuestionFactory.php
│   │   └── ExamAssignmentFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php          # modifié (rôle, status)
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_06_01_000001_drop_legacy_submissions.php     # drop ancienne table
│   │   ├── 2026_06_01_000002_create_platform_settings_table.php
│   │   ├── 2026_06_01_000003_create_exams_table.php
│   │   ├── 2026_06_01_000004_create_exam_sections_table.php
│   │   ├── 2026_06_01_000005_create_questions_table.php
│   │   ├── 2026_06_01_000006_create_exam_assignments_table.php
│   │   ├── 2026_06_01_000007_create_submissions_table.php
│   │   ├── 2026_06_01_000008_create_attachments_table.php
│   │   ├── 2026_06_01_000009_create_incidents_table.php
│   │   ├── 2026_06_01_000010_create_api_usage_logs_table.php
│   │   └── 2026_06_01_000011_create_push_subscriptions_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── AdminUserSeeder.php
├── public/
│   ├── index.php
│   └── sw.js                           # service worker Web Push
├── resources/
│   ├── css/
│   │   └── app.css                     # Tailwind directives
│   ├── js/
│   │   ├── app.js                      # bootstrap Alpine + Echo
│   │   ├── echo.js
│   │   ├── exam-builder.js             # Alpine logic du builder
│   │   ├── exam-runtime.js             # Alpine logic anti-triche + auto-save
│   │   ├── live-monitor.js             # Alpine logic dashboard live
│   │   └── push-subscribe.js
│   └── views/
│       ├── admin/
│       │   ├── dashboard.blade.php
│       │   ├── teachers.blade.php
│       │   ├── settings.blade.php
│       │   └── usage.blade.php
│       ├── auth/                       # Breeze
│       ├── components/                 # Blade components
│       │   ├── exam-builder/
│       │   │   ├── section.blade.php
│       │   │   ├── question-vf.blade.php
│       │   │   ├── question-qcm.blade.php
│       │   │   ├── question-short.blade.php
│       │   │   ├── question-essay.blade.php
│       │   │   ├── question-code.blade.php
│       │   │   └── question-file.blade.php
│       │   └── monitor/
│       │       ├── student-card.blade.php
│       │       └── incident-feed.blade.php
│       ├── emails/
│       │   ├── exam-assignment.blade.php
│       │   ├── grade.blade.php
│       │   └── teacher-approved.blade.php
│       ├── layouts/
│       │   ├── app.blade.php           # layout prof/admin
│       │   └── exam.blade.php          # layout étudiant (verrouillé)
│       ├── student/
│       │   ├── entry.blade.php         # écran de consentement
│       │   ├── runtime.blade.php       # examen actif
│       │   ├── locked.blade.php        # examen verrouillé
│       │   └── submitted.blade.php
│       └── teacher/
│           ├── dashboard.blade.php
│           ├── exams/
│           │   ├── index.blade.php
│           │   ├── create.blade.php
│           │   ├── edit.blade.php
│           │   ├── monitor.blade.php
│           │   └── grading.blade.php
│           └── exams/builder.blade.php
├── routes/
│   ├── web.php          # racine: redirige selon rôle ; auth (Breeze)
│   ├── admin.php        # toutes les routes /admin/*
│   ├── teacher.php      # toutes les routes /teacher/*
│   ├── student.php      # toutes les routes /exam/{token}/* (signed URL middleware)
│   ├── api.php          # endpoints AJAX étudiant + prof
│   ├── channels.php     # broadcast auth
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/
│   │   ├── Admin/
│   │   ├── Auth/
│   │   ├── Teacher/
│   │   │   ├── ExamBuilderTest.php
│   │   │   ├── ExamPublishTest.php
│   │   │   ├── ExamMonitorTest.php
│   │   │   └── GradingTest.php
│   │   ├── Student/
│   │   │   ├── ExamEntryTest.php
│   │   │   ├── ExamRuntimeTest.php
│   │   │   ├── IncidentReportTest.php
│   │   │   └── ExamSubmitTest.php
│   │   └── Security/
│   │       ├── TokenSignatureTest.php
│   │       ├── ExamLockTest.php
│   │       └── ApiKeyVaultTest.php
│   ├── Unit/
│   │   ├── Domain/
│   │   │   ├── AutoGraderTest.php
│   │   │   └── ScoreCalculatorTest.php
│   │   └── Services/
│   │       ├── ClaudeApiClientTest.php
│   │       └── ClaudeExportFormatterTest.php
│   └── TestCase.php
├── .env.example                                # avec REVERB, REDIS, S3, VAPID, CLAUDE_API_BASE
├── composer.json
├── package.json
├── phpunit.xml
└── vite.config.js
```

### 5.2 Schéma de base de données détaillé

```sql
-- users
id BIGSERIAL PK
name VARCHAR(255) NOT NULL
email VARCHAR(255) UNIQUE NOT NULL
password VARCHAR(255) NOT NULL
email_verified_at TIMESTAMP NULL
role VARCHAR(20) NOT NULL DEFAULT 'teacher'  -- 'admin' | 'teacher'
status VARCHAR(20) NOT NULL DEFAULT 'pending' -- 'pending' | 'active' | 'disabled'
remember_token VARCHAR(100) NULL
created_at, updated_at

-- platform_settings (KV chiffré)
id BIGSERIAL PK
key VARCHAR(100) UNIQUE NOT NULL          -- ex: 'claude.api_key', 'claude.model', 'claude.monthly_budget_cents'
encrypted_value TEXT NULL
updated_by BIGINT FK users.id
updated_at TIMESTAMP

-- exams
id BIGSERIAL PK
teacher_id BIGINT NOT NULL FK users.id ON DELETE CASCADE
title VARCHAR(255) NOT NULL
description TEXT NULL
duration_minutes INT NOT NULL                          -- choisi par le prof
opens_at TIMESTAMP NULL
closes_at TIMESTAMP NULL
status VARCHAR(20) NOT NULL DEFAULT 'draft'            -- 'draft' | 'published' | 'closed'
security_settings JSONB NOT NULL DEFAULT '{}'          -- toggles anti-triche par examen
claude_prompt_template TEXT NULL                       -- éditable par le prof
created_at, updated_at
INDEX idx_exams_teacher_status (teacher_id, status)

-- exam_sections
id BIGSERIAL PK
exam_id BIGINT NOT NULL FK exams.id ON DELETE CASCADE
"order" INT NOT NULL
title VARCHAR(255) NOT NULL
instructions TEXT NULL
created_at, updated_at
INDEX idx_exam_sections_exam_order (exam_id, "order")

-- questions
id BIGSERIAL PK
exam_section_id BIGINT NOT NULL FK exam_sections.id ON DELETE CASCADE
"order" INT NOT NULL
type VARCHAR(20) NOT NULL                              -- enum QuestionType
prompt TEXT NOT NULL
points NUMERIC(6,2) NOT NULL DEFAULT 1
bareme_text TEXT NULL                                  -- aide à la correction Claude
autograde_config JSONB NULL                            -- ex: {"correct":"VRAI","penalty":-0.5}
choices JSONB NULL                                     -- ex: [{"key":"A","label":"..."}]
created_at, updated_at
INDEX idx_questions_section_order (exam_section_id, "order")

-- exam_assignments
id BIGSERIAL PK
exam_id BIGINT NOT NULL FK exams.id ON DELETE CASCADE
student_email VARCHAR(255) NOT NULL
student_name VARCHAR(255) NOT NULL
student_matricule VARCHAR(100) NULL
student_group VARCHAR(100) NULL
access_token VARCHAR(64) UNIQUE NOT NULL               -- nonce pour la signed URL
opened_at TIMESTAMP NULL
locked BOOLEAN NOT NULL DEFAULT FALSE
locked_reason VARCHAR(100) NULL
locked_at TIMESTAMP NULL
submitted_at TIMESTAMP NULL
ip INET NULL
user_agent TEXT NULL
created_at, updated_at
UNIQUE (exam_id, student_email)
INDEX idx_assignments_token (access_token)

-- submissions
id BIGSERIAL PK
exam_assignment_id BIGINT UNIQUE NOT NULL FK exam_assignments.id ON DELETE CASCADE
answers JSONB NOT NULL DEFAULT '{}'                    -- {question_id: <answer>}
auto_score NUMERIC(8,2) NULL                           -- V/F + QCM
manual_score NUMERIC(8,2) NULL                         -- ouvertes
total_score NUMERIC(8,2) NULL
status VARCHAR(20) NOT NULL DEFAULT 'in_progress'      -- 'in_progress' | 'submitted' | 'auto_graded' | 'graded' | 'sent'
graded_at TIMESTAMP NULL
sent_at TIMESTAMP NULL
claude_raw_response TEXT NULL
claude_grade_details JSONB NULL
created_at, updated_at

-- attachments  (polymorphic: question OR submission)
id BIGSERIAL PK
attachable_type VARCHAR(50) NOT NULL                   -- 'question' | 'submission'
attachable_id BIGINT NOT NULL
question_id BIGINT NULL FK questions.id                -- pour les pièces jointes de réponse: à quelle question
filename VARCHAR(255) NOT NULL
mime_type VARCHAR(100) NOT NULL
size_bytes BIGINT NOT NULL
storage_path VARCHAR(500) NOT NULL                     -- chemin S3
created_at, updated_at
INDEX idx_attachments_polymorphic (attachable_type, attachable_id)

-- incidents
id BIGSERIAL PK
exam_assignment_id BIGINT NOT NULL FK exam_assignments.id ON DELETE CASCADE
type VARCHAR(50) NOT NULL                              -- enum IncidentType
severity VARCHAR(20) NOT NULL DEFAULT 'warning'        -- 'info' | 'warning' | 'critical'
payload JSONB NOT NULL DEFAULT '{}'
ip INET NULL
user_agent TEXT NULL
occurred_at TIMESTAMP NOT NULL
created_at TIMESTAMP NOT NULL
INDEX idx_incidents_assignment_time (exam_assignment_id, occurred_at)
INDEX idx_incidents_type (type)

-- api_usage_logs
id BIGSERIAL PK
teacher_id BIGINT NOT NULL FK users.id
exam_id BIGINT NULL FK exams.id ON DELETE SET NULL
provider VARCHAR(50) NOT NULL DEFAULT 'anthropic'
model VARCHAR(100) NOT NULL
tokens_in INT NOT NULL
tokens_out INT NOT NULL
cost_cents INT NOT NULL                                -- USD/EUR cents
status VARCHAR(20) NOT NULL                            -- 'ok' | 'error' | 'rate_limited'
occurred_at TIMESTAMP NOT NULL
created_at TIMESTAMP NOT NULL
INDEX idx_api_usage_teacher_time (teacher_id, occurred_at)

-- push_subscriptions
id BIGSERIAL PK
user_id BIGINT NOT NULL FK users.id ON DELETE CASCADE
endpoint VARCHAR(500) NOT NULL
p256dh_key VARCHAR(255) NOT NULL
auth_token VARCHAR(255) NOT NULL
user_agent TEXT NULL
created_at, updated_at
UNIQUE (user_id, endpoint)
```

### 5.3 Architectural Boundaries

**API Boundaries**
- **Routes publiques signées** (`/exam/{token}/*`) : seules routes accessibles sans login Laravel. Middleware `signed` + `ResolveExamAssignment` + `ExamIsLive`.
- **Routes admin** (`/admin/*`) : middleware `auth` + `EnsureAdminRole`.
- **Routes prof** (`/teacher/*`) : middleware `auth` + `EnsureTeacherIsActive`.
- **AJAX étudiant** (`/api/student/{token}/*`) : middleware `signed` + `ResolveExamAssignment`.
- **WebSocket channels** : `routes/channels.php` — autorisation explicite par channel.

**Component Boundaries**
- Contrôleurs ne contiennent **jamais** de logique métier — uniquement validation (FormRequest) + appel d'un Service + retour de vue/JSON.
- Services peuvent appeler d'autres Services mais **jamais** des contrôleurs.
- Domain code (`app/Domain/*`) **ne dépend pas** de Laravel — testable en pure unit tests.
- Events broadcastés sont les **seuls** émetteurs vers le frontend en temps réel. Pas de polling AJAX pour le live monitor.

**Data Boundaries**
- Eloquent Models = porteurs de relations + casts + scopes. Pas de logique métier dans les Models au-delà de `scopeActive()` ou des accessors triviaux.
- Toute mutation traversant plusieurs tables → `DB::transaction()` dans un Service.
- Le client (Alpine + JS) ne reçoit JAMAIS les `autograde_config` des questions (filtrage côté Resource / view).

### 5.4 Requirements to Structure Mapping

| Feature (brief) | Modules code |
|---|---|
| Compte prof + admin console | `app/Http/Controllers/Admin/*`, `app/Http/Controllers/Auth/*`, `resources/views/admin/*`, `EnsureAdminRole`, `EnsureTeacherIsActive` |
| Builder visuel d'examens | `Teacher/ExamBuilderController`, `Services/Exam/ExamBuilderService`, `resources/views/teacher/exams/builder.blade.php`, `resources/views/components/exam-builder/*`, `resources/js/exam-builder.js` |
| Liens uniques étudiants | `Services/Exam/AssignmentTokenGenerator`, `Mail/ExamAssignmentMailable`, `Jobs/SendAssignmentEmailJob` |
| Environnement examen verrouillé | `Http/Controllers/Student/*`, `Http/Middleware/ExamIsLive`, `resources/views/student/runtime.blade.php`, `resources/js/exam-runtime.js`, `resources/views/layouts/exam.blade.php` |
| Détection + verrouillage anti-triche | `Services/Security/IncidentRecorder`, `Services/Security/ExamLockService`, `Http/Controllers/Student/IncidentReportController`, `Domain/Incident/*`, `Events/IncidentRecorded`, `Events/StudentLocked` |
| Dashboard live | `Teacher/ExamMonitorController`, `resources/views/teacher/exams/monitor.blade.php`, `resources/js/live-monitor.js`, `routes/channels.php` |
| Notif push + email | `Notifications/IncidentRaisedNotification`, `Services/Notification/WebPushService`, `Services/Notification/PushSubscriptionService`, `Mail/*Mailable` |
| Auto-grading V/F + QCM | `Domain/Exam/AutoGrader`, `Services/Grading/AutoGradingService` |
| Correction Claude hybride | `Services/Grading/ClaudeExportFormatter` (markdown), `Services/Grading/ClaudeApiClient` + `ClaudeGradingService` (API), `Jobs/DispatchClaudeGradingJob`, `Services/Grading/GradeImportService` |
| Envoi notes par email | `Mail/GradeMailable`, `Jobs/SendGradeEmailJob`, `Teacher/GradingController::sendGrade` / `::sendAllGrades` |
| Clé API mutualisée | `Services/Security/PlatformApiKeyVault`, `Models/PlatformSetting`, `Admin/PlatformSettingsController` |
| Monitoring conso API | `Models/ApiUsageLog`, `Admin/ApiUsageController`, écrit par `ClaudeApiClient` après chaque appel |

### 5.5 Integration Points & Data Flow

**Flow critique 1 : Étudiant ouvre son lien**
1. `GET /exam/{token}` → middleware `signed` valide la signature URL Laravel.
2. `ResolveExamAssignment` charge `ExamAssignment` par `access_token`.
3. `ExamIsLive` vérifie : examen `published`, fenêtre temps OK, `locked = false`, `submitted_at = null`.
4. `ExamEntryController::show` rend `student/entry.blade.php` (consentement + résumé).
5. Étudiant clique "Démarrer" → POST `/exam/{token}/start` → `opened_at = now()`, redirection vers `/exam/{token}/run`.
6. Le shell `student/runtime.blade.php` charge Alpine + `exam-runtime.js` qui demande fullscreen et démarre les listeners Visibility / blur / keydown.

**Flow critique 2 : Étudiant change d'onglet (cas central)**
1. Client : event `visibilitychange`. Alpine envoie `POST /api/student/{token}/incidents` avec `{type: "tab_blur", payload: {...}}`.
2. `IncidentReportController::store` valide via `ReportIncidentRequest`.
3. Service `IncidentRecorder::record(assignment, type, payload)` :
   - Crée la ligne dans `incidents` (transaction).
   - Si `type` est dans la liste majeure → appelle `ExamLockService::lock(assignment, reason: type)`.
   - Broadcast `IncidentRecorded` sur `private-exam.{exam_id}.monitor`.
   - Si lock → broadcast `StudentLocked` sur le même channel + sur `private-student.{assignment_id}`.
   - Dispatch `IncidentRaisedNotification` au prof (canaux : mail + webpush).
4. Le dashboard live du prof (`live-monitor.js` via Echo) reçoit l'event, met à jour la carte étudiant en mode "verrouillé".
5. Le navigateur du prof (s'il a un onglet ouvert ou non) reçoit la notif push via service worker.
6. Côté étudiant : page bascule sur `student/locked.blade.php` (Alpine listenant `private-student.{assignment_id}:ExamLocked`).

**Flow critique 3 : Prof déverrouille**
1. Dashboard prof → bouton "Redonner l'accès" sur la carte étudiant.
2. `POST /teacher/assignments/{assignment}/unlock` → `Teacher/AssignmentController::unlock`.
3. Policy : prof doit posséder l'examen de l'assignment.
4. `ExamLockService::unlock(assignment, by: teacher)` :
   - `UPDATE exam_assignments SET locked=false, locked_reason=null` (transaction).
   - Log incident `type='unlocked_by_teacher'` (audit trail).
   - Broadcast `ExamUnlockedByTeacher` sur `private-student.{assignment_id}` + `private-exam.{id}.monitor`.
5. Côté étudiant : Alpine reçoit l'event → recharge `/exam/{token}/run`.

**Flow critique 4 : Soumission + Claude grading**
1. Étudiant soumet (ou chronomètre serveur atteint 0) → `POST /exam/{token}/submit`.
2. `ExamSubmitController::store` : passe le `Submission.status` à `'submitted'`, calcule `submitted_at`.
3. `AutoGradingService::grade(submission)` (synchrone, rapide) : applique `Domain\Exam\AutoGrader` sur les questions V/F + QCM → `auto_score`.
4. Status devient `'auto_graded'`. Broadcast `StudentSubmitted`.
5. Plus tard — le prof depuis son dashboard de grading clique "Corriger avec Claude API" :
6. `Teacher/GradingController::dispatchClaude` → `DispatchClaudeGradingJob::dispatch($exam, $teacher)` (queue Redis).
7. Job worker :
   - `PlatformApiKeyVault::getDecrypted('claude.api_key')`.
   - `ClaudeExportFormatter` produit le prompt + les copies.
   - `ClaudeApiClient::send` (Guzzle, timeout 180 s, retry 2×).
   - `GradeImportService::importFromClaudeResponse` parse le JSON, met à jour les `submissions`.
   - Log `ApiUsageLog` (tokens, cost).
   - Status devient `'graded'`.
8. Le prof voit en live (broadcast) que les copies passent à `'graded'`. Il peut alors lancer `SendBulkGradeEmailsJob`.

### 5.6 Development Workflow Integration

**Dev local**
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan vapid:generate                 # commande custom à créer
docker compose up -d postgres redis minio  # services
php artisan migrate --seed                 # AdminUserSeeder
composer dev                               # lance: php artisan serve + queue:listen + pail + npm run dev
# dans un terminal séparé :
php artisan reverb:start --debug
```

**Build prod**
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan migrate --force
# Supervisor: reverb, queue:work, schedule:work
```

**Schedule (`routes/console.php`)**
- `php artisan exam:purge-expired-assignments --daily-at=03:00`
- `php artisan exam:send-reminders --hourly` (rappels avant ouverture examen)

---

## 6. Architecture Validation Results

### 6.1 Coherence Validation ✅

**Decision Compatibility**
- Laravel 13 ⇆ PHP 8.3 ⇆ Reverb 1.x ⇆ Breeze 2.x : matrice de versions cohérente (tous supportés ensemble).
- PostgreSQL 16 ⇆ Eloquent ⇆ JSONB columns : combo standard, aucune incompatibilité.
- Alpine.js + Echo + Pusher protocol + Reverb : pile officiellement supportée par Laravel.
- Web Push VAPID natif : aucun coupling SaaS.
- Pas de contradiction détectée entre les décisions.

**Pattern Consistency**
- Naming (`snake_case` DB ↔ `camelCase` PHP via Eloquent casts) : pattern Laravel standard, géré nativement.
- Event broadcast → Echo → Alpine listener : chaîne complète documentée et cohérente.
- Form Requests + Policies + Services : trois couches séparées sans recouvrement.

**Structure Alignment**
- Le découpage `Admin/Teacher/Student` dans Controllers + Views + Requests reflète la frontière de sécurité (3 rôles, 3 surfaces).
- `Domain/` isolé de Laravel = testable et migrable.
- Services par bounded context (`Exam/`, `Grading/`, `Security/`, `Notification/`) : faible couplage.

### 6.2 Requirements Coverage Validation ✅

| Feature brief | Couverte ? | Section archi |
|---|---|---|
| Compte prof + builder examens | ✅ | §3.1 (C2), §5.4 |
| Lien unique nominatif étudiant | ✅ | §3.2 (I10), §5.2 (`exam_assignments.access_token`), §5.5 flow 1 |
| Anti-triche (Visibility, Fullscreen, blocages, DevTools) | ✅ | §5.5 flow 2, `resources/js/exam-runtime.js`, addendum brief §1 |
| Chronomètre serveur | ✅ | §3.1 (C11), `ExamHeartbeatController`, `ExamTimerService` |
| Verrouillage auto + déverrouillage manuel | ✅ | §3.2 (I4), §5.5 flow 2 & 3, `ExamLockService` |
| Dashboard live WebSocket | ✅ | §3.1 (C4), Events broadcastés, channels |
| Push + email | ✅ | §3.1 (C5), `IncidentRaisedNotification` multi-canal |
| Auto-grading V/F+QCM serveur | ✅ | `Domain/Exam/AutoGrader`, `AutoGradingService` |
| Correction Claude hybride | ✅ | §5.5 flow 4, `ClaudeExportFormatter` + `ClaudeApiClient` |
| Clé API mutualisée par admin | ✅ | `PlatformApiKeyVault`, `platform_settings` |
| Pas de compte étudiant | ✅ | Token signé + middleware, aucune table `students` |
| Types questions (6) | ✅ | enum `QuestionType`, components Blade dédiés |
| Envoi notes email | ✅ | `GradeMailable`, `SendGradeEmailJob`, bulk via job |
| Monitoring conso API | ✅ | `api_usage_logs`, `Admin/ApiUsageController` |
| NFR sécurité chiffrement | ✅ | `Crypt::encryptString`, signed URLs, HTTPS-only |
| NFR audit | ✅ | `incidents` append-only, `api_usage_logs` |
| NFR latence < 2s incident → prof | ✅ | broadcast Reverb (sub-100ms LAN, <1s WAN typique) |

### 6.3 Implementation Readiness Validation ✅

- ✅ Toutes les versions techno spécifiées dans §2.2 et §2.3.
- ✅ Schéma DB complet (§5.2) — un agent peut générer les migrations directement.
- ✅ Naming conventions exhaustives (§4.1) — un agent ne devrait pas hésiter sur `users` vs `Users`, etc.
- ✅ Formats API (§4.3) — un agent sait exactement la forme des réponses JSON.
- ✅ Flows critiques décrits étape par étape (§5.5) — implémentables sans interpolation.
- ✅ Project tree complet (§5.1) — un agent sait où placer chaque nouveau fichier.

### 6.4 Gap Analysis

**Gaps critiques** : aucun bloquant identifié.

**Gaps importants à traiter avant la story #1**
- **VAPID keys** : il faut une commande artisan custom `php artisan vapid:generate` (pas natif Laravel). À documenter en story de setup.
- **Politique de rétention RGPD** : non spécifiée dans le brief. À écrire avant tout déploiement réel. Le code est neutre (purge possible).
- **Politique navigateurs supportés** : énoncée dans le brief (Chrome/Edge/Firefox récents). Doit apparaître dans `student/entry.blade.php` (consentement) + tests E2E ciblés.
- **Schéma `security_settings_json`** sur `exams` : structure exacte à figer (toggles par examen : `enforce_fullscreen`, `lock_on_first_offense`, etc.). À détailler dans la story du builder.

**Gaps nice-to-have**
- Sentry / observability prod — post-MVP.
- Tests E2E (Playwright) — post-MVP, tests Feature PHPUnit suffisent pour le MVP.
- CI/CD GitHub Actions — recommandé dès le sprint 1 mais pas bloquant.

### 6.5 Architecture Completeness Checklist

**Requirements Analysis**
- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed
- [x] Technical constraints identified
- [x] Cross-cutting concerns mapped

**Architectural Decisions**
- [x] Critical decisions documented with versions
- [x] Technology stack fully specified
- [x] Integration patterns defined
- [x] Performance considerations addressed

**Implementation Patterns**
- [x] Naming conventions established
- [x] Structure patterns defined
- [x] Communication patterns specified
- [x] Process patterns documented

**Project Structure**
- [x] Complete directory structure defined
- [x] Component boundaries established
- [x] Integration points mapped
- [x] Requirements to structure mapping complete

### 6.6 Architecture Readiness Assessment

**Overall Status : READY WITH MINOR GAPS**

Justification : tous les items de la checklist sont cochés et aucun gap critique n'a été identifié, mais trois gaps importants (VAPID, RGPD, schéma `security_settings_json` détaillé) doivent être traités au tout début de l'implémentation. Ils ne bloquent pas le démarrage mais sont nécessaires avant le sprint 2.

**Confidence Level : élevé**
- Le brief était dense et clair.
- Le code existant donne un point de départ concret pour les patterns Laravel.
- Aucune technologie exotique : tout le stack est boring (Laravel + PostgreSQL + Redis + Alpine).
- Le seul vrai pari technique est Reverb (encore jeune), mais c'est officiellement supporté Laravel et facilement remplaçable par Pusher SaaS en cas de besoin.

**Key Strengths**
- Sécurité examen multicouche, source de vérité serveur partout où ça compte.
- Pattern Service + Form Request + Policy strictement appliqué → testable + auditable.
- Réutilisation maximale du code existant (auto-grading V/F+QCM, export Claude markdown).
- Architecture monolithe Laravel = developer productivity au max pour une équipe solo.
- Aucun lock-in SaaS (Reverb auto-hébergé, Web Push natif VAPID).

**Areas for Future Enhancement**
- Multi-tenant institutionnel (admin par institution, branding) — V2.
- Quotas + facturation API par prof — V2.
- Banque de questions partagée + analytics pédagogiques — V2.
- Proctoring webcam (option, anti-triche niveau 2) — V3.
- Découpage en services (extract grading en microservice) — uniquement si la charge le justifie. Pas avant.

---

## 7. Implementation Handoff

### 7.1 AI Agent Guidelines

Tout agent (humain ou IA) implémentant ExamGuard **doit** :
1. Lire ce document + le brief + l'addendum avant toute story.
2. Respecter strictement les patterns de nommage (§4.1) et les conventions de structure (§4.2).
3. Toute logique métier dans un Service ; jamais dans un Controller ou un Model.
4. Toute mutation cross-table dans une `DB::transaction()`.
5. Toute autorisation via Policy / Gate ; jamais de `if` ad-hoc.
6. Toute validation via Form Request ; jamais de `$request->validate()` inline.
7. Tout event broadcast via classe typée `implements ShouldBroadcast`.
8. Tout endpoint étudiant via signed URL — jamais d'`id` exposé.
9. Tests Feature systématiques pour chaque controller + tests Unit pour le Domain pur.
10. En cas de doute architectural → relire ce document avant de coder. En cas d'absence de réponse → demander à Winston (ou à l'humain) plutôt qu'improviser.

### 7.2 First Implementation Priority

**Story #1 — Setup infrastructure et auth**
```bash
# Composer
composer require laravel/breeze:^2.3 laravel/reverb:^1.0 minishlink/web-push:^9.0 league/flysystem-aws-s3-v3:^3.0 predis/predis:^2.0

# Breeze + Reverb
php artisan breeze:install blade
php artisan reverb:install
php artisan migrate:fresh                                  # base vide, on repart de zéro

# NPM
npm install alpinejs@^3.14 laravel-echo@^1.16 pusher-js@^8.4
npm install

# Postgres + Redis + MinIO (docker-compose à écrire)
docker compose up -d postgres redis minio

# Custom commands à créer:
#   php artisan vapid:generate        (pour Web Push)
#   php artisan admin:make            (pour créer le premier admin en CLI)
```

**Stories suivantes recommandées (dans l'ordre)**
1. Story #1 — Setup infra + auth Breeze (cf. ci-dessus).
2. Story #2 — Rôles + `EnsureAdminRole` + `EnsureTeacherIsActive` + console admin minimale (liste profs, valider/désactiver).
3. Story #3 — `PlatformSettingsController` + `PlatformApiKeyVault` (saisir/chiffrer la clé API Claude).
4. Story #4 — CRUD examens (squelette : `exams`, `exam_sections`, `questions` + builder Blade + Alpine, types VF + QCM uniquement pour démarrer).
5. Story #5 — Types de questions étendus (short, essay, code, file_upload).
6. Story #6 — `exam_assignments` + `AssignmentTokenGenerator` + `ExamAssignmentMailable` + import CSV étudiants.
7. Story #7 — Runtime étudiant *sans* anti-triche (consentement, fullscreen optionnel, auto-save).
8. Story #8 — Couche anti-triche client (Visibility, Fullscreen forcé, blocages clavier/copier-coller, détection DevTools, incident reporter).
9. Story #9 — `IncidentRecorder` + `ExamLockService` côté serveur, déverrouillage manuel.
10. Story #10 — Reverb + Echo + dashboard live prof (`monitor.blade.php` + `live-monitor.js`).
11. Story #11 — Web Push (VAPID, service worker, abonnement prof, envoi via `WebPushService`).
12. Story #12 — Email notifications incidents.
13. Story #13 — `AutoGradingService` + statut `auto_graded`.
14. Story #14 — `ClaudeExportFormatter` (mode copy/paste, repartir du code existant `AdminController::formatForClaude`).
15. Story #15 — `ClaudeApiClient` + `ClaudeGradingService` + queue + `api_usage_logs`.
16. Story #16 — `GradeImportService` + envoi des notes (bulk + individuel).
17. Story #17 — Tests Feature critiques (token signature, exam lock, API key vault) + tests Unit Domain.

### 7.3 Reference Document Status

Ce document devient la **source de vérité technique** pour ExamGuard. Tout écart entre l'implémentation et ce document doit, soit :
- Faire l'objet d'une **PR sur ce fichier** (mise à jour de l'architecture) — la cible bouge, c'est OK ;
- Soit être **flaggué comme tech debt** dans le tracker.

L'architecture n'est pas immuable, mais elle est partagée et explicite.

---

*Architecture v1.0 — produite le 2026-05-23 par Winston (BMad System Architect) en mode batch (auto mode). Brief associé : `_bmad-output/planning-artifacts/briefs/brief-exam-2026-05-23/`.*
